<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FeeReceiptLog;
use App\Models\CustomerFee;
use App\Models\Notification;
use App\Models\Remark;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WebCustomerFeeController extends Controller
{
    public function receipt(Customer $customer)
    {
        $this->authorizeFeeEntry($customer);

        $customer->load(['fees.user', 'counselor', 'telecaller']);
        $total = $customer->fees->sum(fn ($fee) => $fee->signedAmount());

        $log = FeeReceiptLog::create([
            'customer_id' => $customer->id,
            'generated_by' => Auth::id(),
            'receipt_no' => 'FR-' . now()->format('YmdHis') . '-' . $customer->id,
            'total_amount' => $total,
        ]);

        ActivityLogger::log(
            Auth::id(),
            'PRINT_FEE_RECEIPT',
            'Generated fee receipt ' . $log->receipt_no . ' for ' . $customer->activitySummary() . ' total ' . number_format((float) $total, 2),
            $customer->id
        );

        return view('customers.fee-receipt', [
            'customer' => $customer,
            'fees' => $customer->fees,
            'receiptLog' => $log,
            'total' => $total,
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $this->authorizeFeeEntry($customer);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'purpose' => ['required', 'string', 'max:255'],
        ]);

        $fee = DB::transaction(function () use ($customer, $validated) {
            $fee = CustomerFee::create([
                'customer_id' => $customer->id,
                'amount' => $validated['amount'],
                'purpose' => trim($validated['purpose']),
                'created_by' => Auth::id(),
            ]);

            $direction = $fee->isRefund() ? 'Refund' : 'Fee';
            $signedAmount = ($fee->isRefund() ? '- ' : '') . number_format((float) $fee->amount, 2);

            Remark::create([
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'message' => "{$direction} added: {$signedAmount}\nPurpose: {$fee->purpose}",
            ]);

            return $fee;
        });

        $direction = $fee->isRefund() ? 'refund' : 'fee';
        ActivityLogger::log(
            Auth::id(),
            'ADD_CUSTOMER_FEE',
            'Added ' . $direction . ' entry ' . number_format((float) $fee->amount, 2) . ' for ' . $customer->activitySummary() . ' (' . $fee->purpose . ')',
            $customer->id
        );

        return redirect()->route('customers.show', $customer)->with('success', 'Fee entry added.');
    }

    private function authorizeFeeEntry(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user || $user->role === 'telecaller') {
            abort(403);
        }

        if (in_array($user->role, ['admin', 'director', 'receptionist'], true)) {
            return;
        }

        if ($user->role === 'counselor' && $customer->assigned_counselor_id === $user->id) {
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
}
