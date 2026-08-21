<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Remark;
use App\Services\ActivityLogger;
use App\Services\FollowUpService;
use App\Services\GoogleChatNotifier;
use App\Services\ProcessTimelineService;
use App\Services\RemarkTagService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RemarkController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $remarkStatusOptions = collect(config('crm.remark_statuses', []))
            ->push('loan assessment')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $validated = $request->validate([
            'message' => 'required|string',
            'status_update' => ['required', Rule::in($remarkStatusOptions)],
            'follow_up_date' => 'nullable|date',
            'tagged_user_ids' => 'nullable|array',
            'tagged_user_ids.*' => 'exists:users,id',
        ]);

        $status = $validated['status_update'] ?? '';
        $requiresFollowUp = config('crm.statuses_requiring_follow_up', []);
        $noFollowUp = config('crm.statuses_no_follow_up', []);

        if (in_array($status, $noFollowUp, true)) {
            $validated['follow_up_date'] = null;
            $customer->followUps()
                ->where('status', 'pending')
                ->update(['status' => 'done']);
        } elseif (in_array($status, $requiresFollowUp, true) && empty($validated['follow_up_date'])) {
            return response()->json(['message' => 'follow_up_date is required for this status'], 422);
        }

        $remark = Remark::create([
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'status_update' => $validated['status_update'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        if (!empty($validated['status_update'])) {
            $customer->status = $validated['status_update'];
            $customer->save();
            if (in_array($validated['status_update'], ['plan drop', 'not eligible'], true)) {
                ProcessTimelineService::completeDropout($customer, $request->user()->id);
            }
        }

        if (!empty($validated['follow_up_date'])) {
            FollowUpService::setNextForCustomer(
                $customer->id,
                $validated['follow_up_date'],
                $customer->assigned_counselor_id ?: ($customer->telecaller_id ?: $request->user()->id)
            );
        }

        RemarkTagService::process(
            $remark,
            $customer,
            $validated['message'],
            $validated['tagged_user_ids'] ?? [],
            $request->user()
        );

        GoogleChatNotifier::send($this->googleChatRemarkMessage(
            $request->user()->name,
            $customer,
            $validated['message'],
            $validated['status_update'] ?? null
        ), 'remarks');

        ActivityLogger::forCustomer($request->user()->id, 'REMARK', $customer, 'added remark');

        return response()->json($remark, 201);
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
