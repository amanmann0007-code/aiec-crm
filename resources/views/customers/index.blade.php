@extends('layouts.app')

@section('page-title', $pageTitle ?? (auth()->user()->role === 'telecaller' ? 'My Leads' : (auth()->user()->role === 'agent' ? 'My Cases' : 'Customers')))

@section('content')
@php
    $currentSort = $sortColumn ?? 'latest';
    $currentDirection = $sortDirection ?? 'desc';
    $sortUrl = function ($column) use ($currentSort, $currentDirection) {
        $params = request()->query();
        unset($params['page']);
        $params['sort'] = $column;
        $params['direction'] = ($currentSort === $column && $currentDirection === 'asc') ? 'desc' : 'asc';

        return url()->current() . '?' . http_build_query($params);
    };
    $sortIcon = function ($column) use ($currentSort, $currentDirection) {
        if ($currentSort !== $column) {
            return 'bi-arrow-down-up';
        }

        return $currentDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
    };
    $statusBadgeClass = function ($status) {
        $status = strtolower(trim((string) $status));
        $classes = [
            'assigned' => 'bg-primary',
            'interested' => 'bg-success',
            'pursuing ielts/pte' => 'bg-info text-dark',
            'in process' => 'bg-warning text-dark',
            'not eligible' => 'bg-danger',
            'plan drop' => 'bg-dark',
            'will visit' => 'status-badge-will-visit',
            'jfi' => 'status-badge-jfi',
        ];

        return $classes[$status] ?? 'bg-secondary';
    };
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    @if(($showStatusFilter ?? true) || ($showProcessFilter ?? true))
    <form method="GET" class="d-flex align-items-center flex-wrap gap-2">
        @if($currentSort !== 'latest')
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
        @endif
        @if($showStatusFilter ?? true)
        <label for="status-filter" class="text-muted small mb-0">Status</label>
        <select id="status-filter" name="status" class="form-select form-select-sm status-filter-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach($statusOptions as $status)
                <option value="{{ $status }}" {{ ($statusFilter ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @endif
        @if($showProcessFilter ?? true)
        <label for="process-stage-filter" class="text-muted small mb-0">Process</label>
        <select id="process-stage-filter" name="process_stage" class="form-select form-select-sm process-filter-select" onchange="this.form.submit()">
            <option value="">All process stages</option>
            @foreach($processStageOptions as $stage)
                <option value="{{ $stage }}" {{ ($processStageFilter ?? '') === $stage ? 'selected' : '' }}>{{ $stage }}</option>
            @endforeach
        </select>
        @endif
    </form>
    @else
        <div class="text-muted small">Showing cases with status <span class="badge {{ $statusBadgeClass('assigned') }}">assigned</span></div>
    @endif
    @if(in_array(auth()->user()->role, ['admin','receptionist','director','agent']))
        <a href="{{ route('customers.create') }}" class="btn btn-primary">+ Add Customer</a>
    @elseif(auth()->user()->role === 'telecaller')
        <a href="{{ route('customers.create-telecaller') }}" class="btn btn-primary">+ Add Lead</a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('pid') }}" class="sortable-heading {{ $currentSort === 'pid' ? 'active' : '' }}">PID <i class="bi {{ $sortIcon('pid') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('name') }}" class="sortable-heading {{ $currentSort === 'name' ? 'active' : '' }}">Name <i class="bi {{ $sortIcon('name') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('phone') }}" class="sortable-heading {{ $currentSort === 'phone' ? 'active' : '' }}">Phone <i class="bi {{ $sortIcon('phone') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('source') }}" class="sortable-heading {{ $currentSort === 'source' ? 'active' : '' }}">Source <i class="bi {{ $sortIcon('source') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('country') }}" class="sortable-heading {{ $currentSort === 'country' ? 'active' : '' }}">Country <i class="bi {{ $sortIcon('country') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('visa_type') }}" class="sortable-heading {{ $currentSort === 'visa_type' ? 'active' : '' }}">Visa <i class="bi {{ $sortIcon('visa_type') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('process') }}" class="sortable-heading {{ $currentSort === 'process' ? 'active' : '' }}">Process <i class="bi {{ $sortIcon('process') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('status') }}" class="sortable-heading {{ $currentSort === 'status' ? 'active' : '' }}">Status <i class="bi {{ $sortIcon('status') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('follow_up') }}" class="sortable-heading {{ $currentSort === 'follow_up' ? 'active' : '' }}">Next Follow Up <i class="bi {{ $sortIcon('follow_up') }}"></i></a></th>
                    <th><a href="{{ $sortUrl('counselor') }}" class="sortable-heading {{ $currentSort === 'counselor' ? 'active' : '' }}">Counselor <i class="bi {{ $sortIcon('counselor') }}"></i></a></th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $c)
                @php
                    $latestProcessStep = $c->processSteps->first();
                    $nextFollowUp = $c->nextFollowUp;
                    $nextFollowUpDate = $nextFollowUp && $nextFollowUp->follow_up_date
                        ? \Carbon\Carbon::parse($nextFollowUp->follow_up_date)
                        : null;
                    $today = \Carbon\Carbon::today();
                    $followUpBadgeClass = 'bg-light text-muted border';
                    if ($nextFollowUpDate) {
                        if ($nextFollowUpDate->lt($today)) {
                            $followUpBadgeClass = 'bg-danger';
                        } elseif ($nextFollowUpDate->isSameDay($today)) {
                            $followUpBadgeClass = 'bg-warning text-dark';
                        } else {
                            $followUpBadgeClass = 'bg-success';
                        }
                    }
                    $strikeStatuses = ['plan drop', 'jfi', 'not eligible'];
                    $shouldStrikeCustomer = in_array(strtolower((string) $c->status), $strikeStatuses, true)
                        || strtolower((string) optional($latestProcessStep)->step_label) === 'dropout';
                @endphp
                <tr class="{{ $shouldStrikeCustomer ? 'customer-row-struck' : '' }}">
                    <td><a href="{{ route('customers.show', $c) }}">{{ $c->pid }}</a></td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->phone }}</td>
                    <td>{{ $c->source ?: '--' }}</td>
                    <td>@include('partials.country-flag', ['code' => $c->country])</td>
                    <td>{{ $c->visa_type }}</td>
                    <td>
                        @if($latestProcessStep)
                            <span class="badge bg-success process-status-tag">{{ $latestProcessStep->step_label }}</span>
                        @else
                            <span class="badge bg-light text-muted border process-status-tag">Not started</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $statusBadgeClass($c->status) }}">{{ $c->status }}</span></td>
                    <td>
                        @if($nextFollowUpDate)
                            <span class="badge {{ $followUpBadgeClass }}">{{ $nextFollowUpDate->format('d M Y') }}</span>
                        @else
                            <span class="badge {{ $followUpBadgeClass }}">--</span>
                        @endif
                    </td>
                    <td>{{ optional($c->counselor)->name ?? '--' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">{{ $emptyMessage ?? 'No customers found.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
        <div class="card-footer">{{ $customers->links() }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .status-filter-select { min-width: 200px; }
    .process-filter-select { min-width: 220px; }
    .sortable-heading {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: inherit;
        text-decoration: none;
        white-space: nowrap;
    }
    .sortable-heading i {
        color: #94a3b8;
        font-size: .85rem;
    }
    .sortable-heading.active,
    .sortable-heading.active i,
    .sortable-heading:hover {
        color: var(--aiec-blue);
    }
    .country-flag-inline {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
    .country-flag-img {
        border-radius: 2px;
        box-shadow: 0 0 0 1px rgba(0,0,0,.08);
        object-fit: cover;
        flex-shrink: 0;
    }
    .process-status-tag {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .status-badge-will-visit {
        background: #7c3aed;
        color: #fff;
    }
    .status-badge-jfi {
        background: #64748b;
        color: #fff;
    }
    .customer-row-struck td:not(:last-child) {
        text-decoration: line-through;
        text-decoration-thickness: 1.5px;
        color: #94a3b8;
    }
    .customer-row-struck a,
    .customer-row-struck .badge {
        text-decoration: line-through;
        text-decoration-thickness: 1.5px;
    }
</style>
@endpush
