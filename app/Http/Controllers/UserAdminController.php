<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    private array $roles = ['admin', 'receptionist', 'counselor', 'telecaller', 'director'];
    private array $statuses = ['active', 'inactive'];

    public function index()
    {
        $users = User::orderBy('name')->paginate(20);

        return view('users.index', [
            'users' => $users,
            'roles' => $this->roles,
            'statuses' => $this->statuses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($this->roles)],
            'status' => ['required', Rule::in($this->statuses)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogger::log(Auth::id(), 'CREATE_USER', 'Created user ' . $user->name . ' as ' . $user->role);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'managedUser' => $user,
            'roles' => $this->roles,
            'statuses' => $this->statuses,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($this->roles)],
            'status' => ['required', Rule::in($this->statuses)],
        ]);

        if ($user->id === Auth::id() && ($validated['role'] !== 'admin' || $validated['status'] !== 'active')) {
            return back()->withErrors(['role' => 'You cannot remove your own active admin access.'])->withInput();
        }

        $before = $user->only(['name', 'email', 'role', 'status']);
        $user->update($validated);
        $changes = $this->formatChanges($before, $user->only(['name', 'email', 'role', 'status']));

        ActivityLogger::log(
            Auth::id(),
            'UPDATE_USER',
            'Updated user ' . $user->name . ($changes ? '. Changes: ' . implode('; ', $changes) : '')
        );

        return redirect()->route('users.edit', $user)->with('success', 'User updated successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogger::log(Auth::id(), 'RESET_USER_PASSWORD', 'Reset password for user ' . $user->name);

        return redirect()->route('users.edit', $user)->with('success', 'Password reset successfully.');
    }

    private function formatChanges(array $before, array $after): array
    {
        $labels = [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
        ];

        $changes = [];
        foreach ($labels as $field => $label) {
            $old = (string) ($before[$field] ?? 'blank');
            $new = (string) ($after[$field] ?? 'blank');

            if ($old !== $new) {
                $changes[] = "{$label}: {$old} -> {$new}";
            }
        }

        return $changes;
    }
}
