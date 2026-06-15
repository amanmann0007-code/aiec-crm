<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserMentionController extends Controller
{
    public function search(Request $request)
    {
        $q = strtolower(trim($request->query('q', '')));
        $user = $request->user();

        $users = User::query()
            ->where('status', 'active')
            ->when($user && $user->role === 'agent', function ($query) {
                $query->where('role', 'admin');
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
}
