<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WebCustomerCollaborationController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $this->authorizeAgentAccess($customer);

        $validated = $request->validate([
            'agent_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'agent')->where('status', 'active');
                }),
            ],
        ]);

        $agentId = (int) $validated['agent_id'];
        if ($agentId === (int) Auth::id() || $agentId === (int) $customer->agent_id) {
            return back()->withErrors(['agent_id' => 'Choose another agent to collaborate on this case.']);
        }

        if ($customer->collaborators()->whereKey($agentId)->exists()) {
            return back()->withErrors(['agent_id' => 'This agent is already collaborating on the case.']);
        }

        $agent = User::whereKey($agentId)->where('role', 'agent')->where('status', 'active')->firstOrFail();

        DB::transaction(function () use ($customer, $agent) {
            $customer->collaborators()->attach($agent->id, ['added_by' => Auth::id()]);

            $country = config('crm.countries')[$customer->country] ?? $customer->country ?? 'the case';
            Notification::create([
                'user_id' => $agent->id,
                'customer_id' => $customer->id,
                'title' => 'Case collaborated with you',
                'message' => 'Agent ' . Auth::user()->name . ' has collaborated ' . $customer->name . ' - ' . $country . ' with you.',
            ]);
        });

        return back()->with('success', $agent->name . ' can now access this case.');
    }

    private function authorizeAgentAccess(Customer $customer): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'agent') {
            abort(403);
        }

        if ((int) $customer->agent_id === (int) $user->id
            || $customer->collaborators()->whereKey($user->id)->exists()) {
            return;
        }

        abort(403);
    }
}
