<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;

class UserMentionController extends Controller
{
    public function search(Request $request)
    {
        $q = strtolower(trim($request->query('q', '')));
        $user = $request->user();
        $customerId = $request->integer('customer_id');

        $allowedAgentIds = null;
        if ($user && $user->role === 'agent') {
            $customer = $customerId ? Customer::find($customerId) : null;
            if (!$customer || !$this->agentHasAccess($customer, $user)) {
                return response()->json([]);
            }

            $allowedAgentIds = collect([$customer->agent_id])
                ->merge($customer->collaborators()->where('users.status', 'active')->pluck('users.id'))
                ->filter()
                ->unique()
                ->values();
        }

        $users = User::query()
            ->where('status', 'active')
            ->when($user && $user->role === 'agent', function ($query) {
                $query->where('role', 'agent');
            })
            ->when($allowedAgentIds !== null, function ($query) use ($allowedAgentIds) {
                $query->whereIn('id', $allowedAgentIds);
            })
            ->when($q !== '', function ($query) use ($q, $user) {
                if ($user && $user->role === 'agent') {
                    $query->where('name', 'like', "{$q}%");
                    return;
                }

                $compact = preg_replace('/[^a-z0-9]/', '', $q);
                $dotted = str_replace([' ', '_', '-'], '.', $q);
                $dashed = str_replace([' ', '_', '.'], '-', $q);

                $query->where(function ($builder) use ($q, $compact, $dotted, $dashed) {
                    $builder->where('name', 'like', "{$q}%")
                        ->orWhere('email', 'like', "{$q}%")
                        ->orWhere('email', 'like', "{$q}@%")
                        ->orWhere('email', 'like', "{$dotted}%")
                        ->orWhere('email', 'like', "{$dashed}%");

                    if ($compact !== '' && $compact !== $q) {
                        $builder->orWhere('email', 'like', "{$compact}%");
                    }
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'email', 'role'])
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'mention' => $user->mentionHandle(),
                ];
            })
            ->values();

        return response()->json($users);
    }

    private function agentHasAccess(Customer $customer, User $user): bool
    {
        return (int) $customer->agent_id === (int) $user->id
            || $customer->collaborators()->whereKey($user->id)->exists();
    }
}
