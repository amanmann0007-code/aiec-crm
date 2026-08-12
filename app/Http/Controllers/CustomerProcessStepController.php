<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerProcessStep;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Services\ProcessTimelineService;
use Illuminate\Support\Facades\Auth;

class CustomerProcessStepController extends Controller
{
    public function complete(Customer $customer, string $stepKey)
    {
        $this->authorizeCustomer($customer);

        $step = ProcessTimelineService::stepForCustomer($customer, $stepKey);

        if (!$step) {
            abort(404, 'Process step not found for this visa type.');
        }

        $processStep = CustomerProcessStep::where('customer_id', $customer->id)
            ->where('step_key', $step['key'])
            ->first();

        if ($processStep) {
            $canCounselorReopenDropout = Auth::user()->role === 'counselor'
                && $customer->assigned_counselor_id === Auth::id()
                && $step['key'] === ProcessTimelineService::DROPOUT_KEY;
            $canAgentReopen = Auth::user()->role === 'agent'
                && $this->agentHasAccess($customer);

            if (!in_array(Auth::user()->role, ['admin', 'director'], true) && !$canCounselorReopenDropout && !$canAgentReopen) {
                abort(403, 'Only admin, director, assigned agent, or assigned counselor for Dropout can reopen this process step.');
            }

            $processStep->delete();

            ActivityLogger::log(
                Auth::id(),
                'PROCESS_STEP_REOPENED',
                Auth::user()->name . ' reopened process step "' . $step['label'] . '" for ' . $customer->activitySummary(),
                $customer->id
            );

            return response()->json([
                'ok' => true,
                'completed' => false,
            ]);
        }

        $processStep = CustomerProcessStep::create([
            'customer_id' => $customer->id,
            'visa_type' => $customer->visa_type,
            'step_key' => $step['key'],
            'step_label' => $step['label'],
            'step_order' => $step['order'],
            'completed_by' => Auth::id(),
            'completed_at' => now(),
        ]);

        if ($step['key'] === ProcessTimelineService::DROPOUT_KEY) {
            $customer->update(['status' => 'plan drop']);
            $customer->followUps()
                ->where('status', 'pending')
                ->update(['status' => 'done']);
        }

        ActivityLogger::log(
            Auth::id(),
            'PROCESS_STEP_COMPLETED',
            Auth::user()->name . ' completed process step "' . $step['label'] . '" for ' . $customer->activitySummary(),
            $customer->id
        );

        return response()->json([
            'ok' => true,
            'completed' => true,
            'completed_at' => $processStep->completed_at->toDateTimeString(),
            'completed_by' => Auth::user()->name,
            'customer_status' => $customer->fresh()->status,
        ]);
    }

    private function authorizeCustomer(Customer $customer): void
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'director'], true)) {
            return;
        }

        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
            return;
        }

        if ($user->role === 'agent') {
            if ($this->agentHasAccess($customer)) {
                return;
            }

            abort(403);
        }

        if (Notification::where('user_id', $user->id)->where('customer_id', $customer->id)->exists()) {
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
}
