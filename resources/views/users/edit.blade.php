@extends('layouts.app')

@section('page-title', 'Manage User')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">User Details</div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $managedUser) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" value="{{ old('name', $managedUser->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $managedUser->email) }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="user-role" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ old('role', $managedUser->role) === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $managedUser->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="agent-fields border rounded p-3 mt-3">
                        <div class="small text-muted mb-2">Agent details</div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input name="agent_contact" class="form-control @error('agent_contact') is-invalid @enderror" value="{{ old('agent_contact', $managedUser->agent_contact) }}">
                            @error('agent_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch / City</label>
                            <input name="agent_branch" class="form-control @error('agent_branch') is-invalid @enderror" value="{{ old('agent_branch', $managedUser->agent_branch) }}">
                            @error('agent_branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Reference From</label>
                            <input name="agent_reference_from" class="form-control @error('agent_reference_from') is-invalid @enderror" value="{{ old('agent_reference_from', $managedUser->agent_reference_from) }}">
                            @error('agent_reference_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary">Save User</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Reset Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.password', $managedUser) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input name="password" type="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input name="password_confirmation" type="password" class="form-control" required minlength="8">
                    </div>
                    <button class="btn btn-warning">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('user-role');
    const agentFields = document.querySelector('.agent-fields');

    function syncAgentFields() {
        const isAgent = role && role.value === 'agent';
        if (agentFields) {
            agentFields.classList.toggle('d-none', !isAgent);
            agentFields.querySelectorAll('input').forEach((input) => {
                input.required = isAgent;
            });
        }
    }

    if (role) {
        role.addEventListener('change', syncAgentFields);
        syncAgentFields();
    }
});
</script>
@endpush
