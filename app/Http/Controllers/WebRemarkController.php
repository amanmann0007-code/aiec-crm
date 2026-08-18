<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
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
    public function storeReminder(Request $request, Customer $customer)
    {
        $this->authorizeCustomerAccess($customer);

        $validated = $request->validate([
            'hours' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
        ]);

        $hours = (int) $validated['hours'];
        $actor = Auth::user();

        Notification::create([
            'user_id' => $actor->id,
            'customer_id' => $customer->id,
            'title' => 'Customer callback reminder',
            'message' => "Reminder set by {$actor->name}: call back {$customer->activitySummary()} in {$hours} " . ($hours === 1 ? 'hour' : 'hours') . '.',
            'remind_at' => now()->addHours($hours),
        ]);

        return back()->with('success', "Reminder set for {$hours} " . ($hours === 1 ? 'hour' : 'hours') . ' from now.');
    }

    public function store(Request $request, Customer $customer)
    {
        $remarkStatusOptions = $this->remarkStatusOptions();

        $validated = $request->validate([
            'message' => 'required|string',
            'status_update' => ['nullable', Rule::in($remarkStatusOptions)],
            'follow_up_date' => 'nullable|date',
            'tagged_user_ids' => 'nullable|array',
            'tagged_user_ids.*' => 'exists:users,id',
        ]);

        if (Auth::user()->role === 'agent' && !empty($validated['tagged_user_ids'])) {
            $taggedUserIds = collect($validated['tagged_user_ids'])->unique()->values();
            $allowedAgentIds = $this->allowedAgentIds($customer);
            $allowedTaggedCount = User::whereIn('id', $taggedUserIds)
                ->whereIn('id', $allowedAgentIds)
                ->where('role', 'agent')
                ->where('status', 'active')
                ->count();

            if ($allowedTaggedCount !== $taggedUserIds->count()) {
                return back()
                    ->withErrors(['message' => 'Agents can only tag active agents collaborating on this case.'])
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

    private function remarkStatusOptions(): array
    {
        return collect(config('crm.remark_statuses', []))
            ->push('loan assessment')
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    private function authorizeCustomerAccess(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if (in_array($user->role, ['admin', 'director', 'receptionist'], true)) {
            return;
        }

        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
            return;
        }

        if ($user->role === 'telecaller' && $customer->telecaller_id === $user->id) {
            return;
        }

        if ($user->role === 'agent' && $this->agentHasAccess($customer)) {
            return;
        }

        abort(403);
    }

    private function agentHasAccess(Customer $customer): bool
    {
        $user = Auth::user();

        return $user && $user->role === 'agent'
            && ((int) $customer->agent_id === (int) $user->id
                || $customer->collaborators()->whereKey($user->id)->exists());
    }

    private function allowedAgentIds(Customer $customer)
    {
        return collect([$customer->agent_id])
            ->merge($customer->collaborators()->where('users.status', 'active')->pluck('users.id'))
            ->filter()
            ->unique()
            ->values();
    }
}
