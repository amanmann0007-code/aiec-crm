@extends('layouts.app')

@section('page-title', 'Visiting Client')

@section('content')
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Country</th>
                    <th>Visa</th>
                    <th>Status</th>
                    <th>Visit Date</th>
                    <th>Telecaller</th>
                    @if($canMarkReady)
                        <th class="text-end">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><a href="{{ route('customers.show', $customer) }}">{{ $customer->pid }}</a></td>
                    <td>{{ $customer->name }}</td>
                    <td><span class="phone-highlight">{{ $customer->phone }}</span></td>
                    <td>@include('partials.country-flag', ['code' => $customer->country])</td>
                    <td>{{ $customer->visa_type }}</td>
                    <td><span class="badge bg-warning text-dark">{{ $customer->status }}</span></td>
                    <td>
                        @if($customer->visit_date)
                            <span class="badge {{ $customer->visit_date->isToday() ? 'bg-warning text-dark' : ($customer->visit_date->isPast() ? 'bg-danger' : 'bg-success') }}">
                                {{ $customer->visit_date->format('d M Y') }}
                            </span>
                        @else
                            <span class="text-muted">--</span>
                        @endif
                    </td>
                    <td>{{ optional($customer->telecaller)->name ?? '--' }}</td>
                    @if($canMarkReady)
                        <td class="text-end">
                            <a href="{{ route('customers.create', ['lead_id' => $customer->id]) }}" class="btn btn-sm btn-primary">Ready</a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canMarkReady ? 9 : 8 }}" class="text-center text-muted py-4">No visiting clients found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
        <div class="card-footer">{{ $customers->links() }}</div>
    @endif
</div>
@endsection
