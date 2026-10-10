@extends('layouts.app')

@section('page-title', 'Tab Entries')

@section('content')
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Duration</th>
                    <th>Course Type</th>
                    <th>Qualification</th>
                    <th>Submitted</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->name }}</td>
                    <td><span class="phone-highlight">{{ $entry->phone }}</span></td>
                    <td>{{ in_array($entry->country, config('crm.durations'), true) ? $entry->country : (config('crm.countries')[$entry->country] ?? $entry->country) }}</td>
                    <td>{{ $entry->visa_type }}</td>
                    <td>{{ $entry->qualification ?? '--' }}</td>
                    <td>{{ $entry->created_at->format('d M Y h:i A') }}</td>
                    <td class="text-end">
                        <a href="{{ route('customers.create', ['entry_id' => $entry->id]) }}" class="btn btn-sm btn-primary">Submit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No tab entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="card-footer">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
