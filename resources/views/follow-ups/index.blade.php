@extends('layouts.app')

@section('page-title', 'My Follow-Ups')

@section('content')
@php
    $sections = [
        'overdue' => ['title' => 'Overdue', 'class' => 'danger', 'items' => $overdue],
        'today' => ['title' => 'Today', 'class' => 'warning', 'items' => $todayList],
        'upcoming' => ['title' => 'Upcoming', 'class' => 'success', 'items' => $upcoming],
    ];
@endphp

@foreach($sections as $key => $section)
<div class="card shadow-sm mb-4 border-{{ $section['class'] }}">
    <div class="card-header bg-{{ $section['class'] }} text-white">{{ $section['title'] }}</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th><th>PID</th><th>Phone</th><th>Source</th><th>Process</th><th>Status</th><th>Follow-up</th><th>Last remark</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($section['items'] as $row)
                @php
                    $c = $row['customer'];
                    $f = $row['follow_up'];
                    $latestProcessStep = $c ? $c->processSteps->first() : null;
                    $followDate = $f->follow_up_date ? \Carbon\Carbon::parse($f->follow_up_date)->format('d M Y') : '--';
                @endphp
                <tr>
                    <td>{{ $c->name ?? '--' }}</td>
                    <td><a href="{{ route('customers.show', $c) }}">{{ $c->pid }}</a></td>
                    <td>{{ $c->phone }}</td>
                    <td>{{ $c->source ?: '--' }}</td>
                    <td>
                        @if($latestProcessStep)
                            <span class="badge bg-success process-status-tag">{{ $latestProcessStep->step_label }}</span>
                        @else
                            <span class="badge bg-light text-muted border process-status-tag">Not started</span>
                        @endif
                    </td>
                    <td><span class="badge bg-secondary">{{ $c->status }}</span></td>
                    <td>{{ $followDate }}</td>
                    <td class="small text-muted">{{ Str::limit($row['last_remark'] ?? '--', 60) }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('customers.show', $c) }}" class="btn btn-sm btn-outline-primary">Open Case</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted text-center py-3">None</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endsection

@push('styles')
<style>
    .process-status-tag {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
</style>
@endpush
