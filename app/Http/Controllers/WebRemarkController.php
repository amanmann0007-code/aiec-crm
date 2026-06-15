<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Remark;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FollowUpService;
use App\Services\GoogleChatNotifier;
use App\Services\ProcessTimelineService;
use App\Services\RemarkTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WebRemarkController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'status_update' => ['nullable', Rule::in(config('crm.remark_statuses', []))],
            'follow_up_date' => 'nullable|date',
            'tagged_user_ids' => 'nullable|array',
            'tagged_user_ids.*' => 'exists:users,id',
        ]);

        if (Auth::user()->role === 'agent' && !empty($validated['tagged_user_ids'])) {
            $taggedCount = count(array_unique($validated['tagged_user_ids']));
            $adminTaggedCount = User::whereIn('id', $validated['tagged_user_ids'])
                ->where('role', 'admin')
                ->where('status', 'active')
                ->count();

            if ($adminTaggedCount !== $taggedCount) {
                return back()
                    ->withErrors(['message' => 'Agents can only tag active admin users.'])
                    ->withInput();
            }
        }

        $status = $validated['status_update'] ?? '';
        $requiresFollowUp = config('crm.statuses_requiring_follow_up', []);
        $noFollowUp = config('crm.statuses_no_follow_up', []);

        if (in_array($status, $noFollowUp, true)) {
            $validated['follow_up_date'] = null;
            $customer->followUps()
                ->where('status', 'pending')
                ->update(['status' => 'done']);
        } elseif (in_array($status, $requiresFollowUp, true) && empty($validated['follow_up_date'])) {
            return back()->withErrors(['follow_up_date' => 'Follow-up date is required for this status.'])->withInput();
        }

        $remark = Remark::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'status_update' => $validated['status_update'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        if (!empty($validated['status_update'])) {
            $customer->update(['status' => $validated['status_update']]);
            if (in_array($validated['status_update'], ['plan drop', 'not eligible'], true)) {
                ProcessTimelineService::completeDropout($customer, Auth::id());
            }
        }

        if (!empty($validated['follow_up_date'])) {
            FollowUpService::setNextForCustomer(
                $customer->id,
                $validated['follow_up_date'],
                $customer->assigned_counselor_id ?: ($customer->telecaller_id ?: Auth::id())
            );
        }

        RemarkTagService::process(
            $remark,
            $customer,
            $validated['message'],
            $validated['tagged_user_ids'] ?? [],
            Auth::user()
        );

        GoogleChatNotifier::send($this->googleChatRemarkMessage(
            Auth::user()->name,
            $customer,
            $validated['message'],
            $validated['status_update'] ?? null
        ), 'remarks');

        ActivityLogger::forCustomer(Auth::id(), 'REMARK', $customer, 'added remark');

        return back()->with('success', 'Remark added.');
    }

    private function googleChatRemarkMessage(string $actorName, Customer $customer, string $message, ?string $status): string
    {
        $lines = [
            "Remark added by {$actorName}",
            'Customer: ' . $customer->activitySummary(),
            'Remark: ' . trim($message),
            'Status: ' . ($status ?: 'No status update'),
        ];

        return implode("\n", $lines);
    }
}
