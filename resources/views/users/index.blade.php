@extends('layouts.app')

@section('page-title', 'Users')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header">Create User</div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="user-role" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ old('role', 'telecaller') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', 'active') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="agent-fields border rounded p-3 mt-3">
                        <div class="small text-muted mb-2">Agent details</div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input name="agent_contact" class="form-control @error('agent_contact') is-invalid @enderror" value="{{ old('agent_contact') }}">
                            @error('agent_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch / City</label>
                            <input name="agent_branch" class="form-control @error('agent_branch') is-invalid @enderror" value="{{ old('agent_branch') }}">
                            @error('agent_branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Reference From</label>
                            <input name="agent_reference_from" class="form-control @error('agent_reference_from') is-invalid @enderror" value="{{ old('agent_reference_from') }}">
                            @error('agent_reference_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" class="form-control" required minlength="8">
                            <div class="form-text agent-password-hint d-none">Leave blank to use default password: admin123</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input name="password_confirmation" type="password" class="form-control" required minlength="8">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3">Create User</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">Existing Users</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Agent Branch</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-secondary">{{ $user->role }}</span></td>
                                <td>{{ $user->role === 'agent' ? ($user->agent_branch ?: '--') : '--' }}</td>
                                <td><span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">{{ $user->status }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('user-role');
    const agentFields = document.querySelector('.agent-fields');
    const password = document.querySelector('input[name="password"]');
    const passwordConfirmation = document.querySelector('input[name="password_confirmation"]');
    const passwordHint = document.querySelector('.agent-password-hint');

    function syncAgentFields() {
        const isAgent = role && role.value === 'agent';
        if (agentFields) {
            agentFields.classList.toggle('d-none', !isAgent);
            agentFields.querySelectorAll('input').forEach((input) => {
                input.required = isAgent;
            });
        }
        if (password && passwordConfirmation) {
            password.required = !isAgent;
            passwordConfirmation.required = !isAgent;
        }
        if (passwordHint) {
            passwordHint.classList.toggle('d-none', !isAgent);
        }
    }

    if (role) {
        role.addEventListener('change', syncAgentFields);
        syncAgentFields();
    }
});
</script>
@endpush
