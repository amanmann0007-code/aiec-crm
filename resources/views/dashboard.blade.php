@extends('layouts.app')

@section('page-title', 'Report')

@section('content')
@php
    $dashboardFilterParams = ($isSuperAdmin ?? false) && !empty($selectedUserId) ? ['user_id' => $selectedUserId] : [];
    $showLeadSourceReport = ($user->role ?? null) !== 'agent';
    $statusBadgeClass = function ($status) {
        $status = strtolower(trim((string) $status));
        $classes = [
            'assigned' => 'bg-primary',
            'interested' => 'bg-success',
            'pursuing ielts/pte' => 'bg-info text-dark',
            'loan assessment' => 'bg-info subtle-bg text-dark',
            'in process' => 'bg-warning text-dark',
            'not eligible' => 'bg-danger',
            'plan drop' => 'bg-dark',
            'will visit' => 'status-badge-will-visit',
            'jfi' => 'status-badge-jfi',
        ];

        return $classes[$status] ?? 'bg-secondary';
    };
@endphp
<form method="GET" class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-end gap-3">
        @if($isSuperAdmin ?? false)
            <div>
                <label for="user_id" class="form-label small text-muted">User</label>
                <select name="user_id" id="user_id" class="form-select form-select-sm dashboard-user-filter">
                    <option value="">All users</option>
                    @foreach($userFilterOptions as $filterUser)
                        <option value="{{ $filterUser->id }}" {{ (string) $selectedUserId === (string) $filterUser->id ? 'selected' : '' }}>
                            {{ $filterUser->name }} ({{ $filterUser->role }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="from" class="form-label small text-muted">From</label>
            <input type="date" name="from" id="from" class="form-control form-control-sm" value="{{ $fromDate->toDateString() }}">
        </div>
        <div>
            <label for="to" class="form-label small text-muted">To</label>
            <input type="date" name="to" id="to" class="form-control form-control-sm" value="{{ $toDate->toDateString() }}">
        </div>
        <button class="btn btn-sm btn-primary">Apply</button>
        <button type="button" class="btn btn-sm btn-outline-primary" id="quick-reports-toggle">Quick reports</button>
        @if(($isSuperAdmin ?? false) && $selectedUser)
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Clear user</a>
        @endif
    </div>
</form>

@if(($isSuperAdmin ?? false) && $selectedUser)
    <div class="alert alert-info py-2 mb-3">
        Showing dashboard stats for <strong>{{ $selectedUser->name }}</strong> ({{ $selectedUser->role }}).
    </div>
@endif

<div id="quick-reports-panel" class="d-none mb-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('dashboard', array_merge($dashboardFilterParams, ['range' => 7])) }}" class="btn btn-sm btn-outline-primary">Last 7 days</a>
        <a href="{{ route('dashboard', array_merge($dashboardFilterParams, ['range' => 14])) }}" class="btn btn-sm btn-outline-primary">Last 14 days</a>
        <a href="{{ route('dashboard', array_merge($dashboardFilterParams, ['range' => 30])) }}" class="btn btn-sm btn-outline-primary">Last 30 days</a>
    </div>
    <div class="row g-3">
        @foreach($quickReports as $key => $report)
            <div class="col-xl-4 col-md-6">
                <a href="{{ route('dashboard', array_merge($dashboardFilterParams, ['range' => $report['days']])) }}" class="text-decoration-none text-reset">
                    <div class="card shadow-sm h-100 quick-report-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="text-muted small">Last {{ $report['days'] }} days</div>
                                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($report['from'])->format('d M') }} - {{ \Carbon\Carbon::parse($report['to'])->format('d M Y') }}</div>
                                </div>
                                <span class="badge bg-primary">View</span>
                            </div>
                            <div class="quick-report-grid">
                                <div><span>{{ $report['customers'] }}</span><small>Customers</small></div>
                                <div><span>{{ $report['followups'] }}</span><small>Pending follow-ups</small></div>
                                <div><span>{{ $report['process_steps'] }}</span><small>Process done</small></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Customers</div>
                <div class="fs-3 fw-bold">{{ $stats['customers'] }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">👤</div>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('follow-ups.index') }}" class="text-decoration-none text-reset">
        <div class="card shadow-sm dashboard-link-card"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Overdue follow-ups</div>
                <div class="fs-3 fw-bold text-danger">{{ $stats['overdue_followups'] }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">⚠️</div>
        </div></div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('follow-ups.index') }}" class="text-decoration-none text-reset">
        <div class="card shadow-sm dashboard-link-card"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Today follow-ups</div>
                <div class="fs-3 fw-bold text-warning">{{ $stats['today_followups'] }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">📝</div>
        </div></div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="{{ route('follow-ups.index') }}" class="text-decoration-none text-reset">
        <div class="card shadow-sm dashboard-link-card"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Future follow-ups</div>
                <div class="fs-3 fw-bold text-success">{{ $stats['future_followups'] }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">✅</div>
        </div></div>
        </a>
    </div>
    @if($stats['users'] !== null)
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Users</div>
            <div class="fs-3 fw-bold">{{ $stats['users'] }}</div>
        </div></div>
    </div>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body dashboard-stat-card">
                <div>
                    <div class="text-muted small">Total {{ ucfirst($enrollmentStats['label']) }}</div>
                    <div class="fs-3 fw-bold">{{ $enrollmentStats['total'] }}</div>
                    <div class="text-muted small">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</div>
                </div>
                <div class="dashboard-stat-emoji" aria-hidden="true">#</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body dashboard-stat-card">
                <div>
                    <div class="text-muted small">Enrolled {{ ucfirst($enrollmentStats['label']) }}</div>
                    <div class="fs-3 fw-bold text-success">{{ $enrollmentStats['enrolled'] }}</div>
                    <div class="text-muted small">In process or process started</div>
                </div>
                <div class="dashboard-stat-emoji" aria-hidden="true">%</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12">
        <div class="card shadow-sm h-100">
            <div class="card-body dashboard-stat-card">
                <div>
                    <div class="text-muted small">Enrollment Ratio</div>
                    <div class="fs-3 fw-bold text-primary">{{ $enrollmentStats['ratio'] }}%</div>
                    <div class="text-muted small">{{ $enrollmentStats['enrolled'] }} / {{ $enrollmentStats['total'] }}</div>
                </div>
                <div class="dashboard-stat-emoji" aria-hidden="true">=</div>
            </div>
        </div>
    </div>
</div>

@if($isSuperAdmin ?? false)
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card shadow-sm"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Fees collected</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($feeStats['collected'] ?? 0, 2) }}</div>
                <div class="text-muted small">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">₹</div>
        </div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card shadow-sm"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Refunds</div>
                <div class="fs-3 fw-bold text-danger">- {{ number_format($feeStats['refunds'] ?? 0, 2) }}</div>
                <div class="text-muted small">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">↩</div>
        </div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card shadow-sm"><div class="card-body dashboard-stat-card">
            <div>
                <div class="text-muted small">Net fees</div>
                <div class="fs-3 fw-bold {{ ($feeStats['net'] ?? 0) < 0 ? 'text-danger' : 'text-primary' }}">{{ number_format($feeStats['net'] ?? 0, 2) }}</div>
                <div class="text-muted small">Collected minus refunds</div>
            </div>
            <div class="dashboard-stat-emoji" aria-hidden="true">=</div>
        </div></div>
    </div>
</div>
@endif

@if($canViewAgentCaseReport ?? false)
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm agent-case-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Agent case report</span>
                <span class="text-muted small">{{ $fromDate->format('d M Y') }} - {{ $toDate->format('d M Y') }}</span>
            </div>
            <div class="card-body">
                @if($agentCaseCounts->sum() > 0)
                    <div id="agent-case-chart"></div>
                @else
                    <div class="text-center text-muted py-5">No agent cases found in this date range.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if($showLeadSourceReport)
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm lead-status-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Lead and source report</span>
                <span class="text-muted small">Selected date range</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <div class="report-chart-panel">
                            <div class="fw-semibold mb-2">Telecaller lead status</div>
                            @if($leadStatusCounts->sum() > 0)
                                <div id="lead-status-chart"></div>
                            @else
                                <div class="text-center text-muted py-5">No telecaller leads found in this date range.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="report-chart-panel">
                            <div class="fw-semibold mb-2">Source distribution</div>
                            @if($sourceCounts->isNotEmpty())
                                <div id="source-chart"></div>
                            @else
                                <div class="text-center text-muted py-5">No sources found in this date range.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-xl-6">
@if($statusCounts->isNotEmpty())
<div class="card shadow-sm status-overview-card h-100">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Customers by status</span>
        <span class="text-muted small">Click a segment or row to view those customers</span>
    </div>
    <div class="card-body">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <div id="status-chart"></div>
            </div>
            <div class="col-lg-6">
                <div class="status-breakdown-list">
                    @foreach($statusCounts as $status => $count)
                        <a href="{{ route('customers.index', ['status' => $status]) }}"
                           class="status-breakdown-item"
                           data-status="{{ $status }}">
                            <span class="status-breakdown-item__dot" data-status="{{ $status }}"></span>
                            <span class="status-breakdown-item__label">{{ $status }}</span>
                            <span class="badge rounded-pill bg-primary">{{ $count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
    </div>
    <div class="col-xl-6">
        <div class="card shadow-sm process-overview-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Latest process by case</span>
                <span class="text-muted small">Click a segment to view those customers</span>
            </div>
            <div class="card-body">
                @if($processTimelineCounts->isNotEmpty())
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-6">
                            <div id="process-chart"></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="process-breakdown-list">
                                @foreach($processTimelineCounts as $stage => $count)
                                    <a href="{{ route('customers.index', ['process_stage' => $stage]) }}"
                                       class="process-breakdown-item"
                                       data-process-stage="{{ $stage }}">
                                        <span class="process-breakdown-item__dot"></span>
                                        <span class="process-breakdown-item__label">{{ $stage }}</span>
                                        <span class="badge rounded-pill bg-primary">{{ $count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-5">No process steps completed in this date range.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <span>Recent customers</span>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>PID</th><th>Name</th><th>Phone</th><th>Source</th><th>Process</th><th>Status</th><th>Counselor</th></tr></thead>
            <tbody>
            @forelse($recentCustomers as $c)
                @php
                    $latestProcessStep = $c->processSteps->first();
                    $strikeStatuses = ['plan drop', 'jfi', 'not eligible'];
                    $shouldStrikeCustomer = in_array(strtolower((string) $c->status), $strikeStatuses, true)
                        || strtolower((string) optional($latestProcessStep)->step_label) === 'dropout';
                @endphp
                <tr class="{{ $shouldStrikeCustomer ? 'customer-row-struck' : '' }}">
                    <td><a href="{{ route('customers.show', $c) }}">{{ $c->pid }}</a></td>
                    <td>{{ $c->name }}</td>
                    <td><span class="phone-highlight">{{ $c->phone }}</span></td>
                    <td>
                        @if(($user->role ?? null) === 'agent')
                            {{ optional($c->agent)->name ?? '--' }}
                        @else
                            {{ $c->source ?: '--' }}
                        @endif
                    </td>
                    <td>
                        @if($latestProcessStep)
                            <span class="badge bg-success process-status-tag">{{ $latestProcessStep->step_label }}</span>
                        @else
                            <span class="badge bg-light text-muted border process-status-tag">Not started</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('customers.index', ['status' => $c->status]) }}" class="badge {{ $statusBadgeClass($c->status) }} text-decoration-none">
                            {{ $c->status }}
                        </a>
                    </td>
                    <td>{{ optional($c->counselor)->name ?? '--' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No customers yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    .status-overview-card #status-chart { min-height: 320px; }
    .process-overview-card #process-chart { min-height: 320px; }
    .agent-case-card #agent-case-chart { min-height: 320px; }
    .lead-status-card #lead-status-chart,
    .lead-status-card #source-chart { min-height: 280px; }
    .report-chart-panel {
        min-height: 340px;
    }
    .dashboard-user-filter { min-width: 240px; }
    .dashboard-stat-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 104px;
    }
    .dashboard-stat-emoji {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 1.65rem;
        line-height: 1;
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
    .dashboard-link-card:hover, .quick-report-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 .35rem 1rem rgba(26, 77, 143, .12) !important;
    }
    .quick-report-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .75rem;
    }
    .quick-report-grid div {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: .65rem;
    }
    .quick-report-grid span {
        display: block;
        font-size: 1.4rem;
        line-height: 1;
        font-weight: 700;
        color: #0f172a;
    }
    .quick-report-grid small {
        color: #64748b;
        font-size: .72rem;
    }
    .status-breakdown-list,
    .process-breakdown-list {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        max-height: 360px;
        overflow-y: auto;
    }
    .status-breakdown-item,
    .process-breakdown-item {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .5rem .75rem;
        border-radius: .5rem;
        text-decoration: none;
        color: #1e293b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: background .15s, border-color .15s;
    }
    .status-breakdown-item:hover,
    .process-breakdown-item:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1e40af;
    }
    .status-breakdown-item__dot,
    .process-breakdown-item__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        background: var(--aiec-blue);
    }
    .status-breakdown-item__label,
    .process-breakdown-item__label {
        flex: 1;
        font-size: .9rem;
        text-transform: capitalize;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quickToggle = document.getElementById('quick-reports-toggle');
    const quickPanel = document.getElementById('quick-reports-panel');

    if (!quickToggle || !quickPanel) return;

    quickToggle.addEventListener('click', function () {
        const hidden = quickPanel.classList.toggle('d-none');
        quickToggle.textContent = hidden ? 'Quick reports' : 'Hide quick reports';
    });
});
</script>
@endpush

@if($statusCounts->isNotEmpty() || $processTimelineCounts->isNotEmpty() || (($canViewAgentCaseReport ?? false) && $agentCaseCounts->sum() > 0) || ($showLeadSourceReport && ($leadStatusCounts->sum() > 0 || $sourceCounts->isNotEmpty())))
@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@if(($canViewAgentCaseReport ?? false) && $agentCaseCounts->sum() > 0)
<script>
(function () {
    const labels = @json($agentCaseCounts->keys()->values());
    const counts = @json($agentCaseCounts->values());
    const chartEl = document.querySelector('#agent-case-chart');
    if (!chartEl) return;

    const chart = new ApexCharts(chartEl, {
        chart: {
            type: 'bar',
            height: Math.max(320, labels.length * 44),
            fontFamily: 'inherit',
            toolbar: { show: false }
        },
        series: [{
            name: 'Cases',
            data: counts
        }],
        xaxis: {
            categories: labels,
            labels: {
                formatter: function (value) {
                    return Math.round(value);
                }
            }
        },
        yaxis: {
            labels: {
                style: { fontWeight: 600 },
                maxWidth: 260
            }
        },
        colors: ['#8b5cf6'],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            offsetX: 8,
            style: {
                colors: ['#0f172a'],
                fontSize: '14px',
                fontWeight: 700
            }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return value + ' case' + (value === 1 ? '' : 's');
                }
            }
        },
        grid: { borderColor: '#e2e8f0' }
    });

    chart.render();
})();
</script>
@endif
@if($showLeadSourceReport && $leadStatusCounts->sum() > 0)
<script>
(function () {
    const labels = @json($leadStatusCounts->keys()->values()->map(fn ($status) => ucfirst($status)));
    const counts = @json($leadStatusCounts->values());
    const chartEl = document.querySelector('#lead-status-chart');
    if (!chartEl) return;

    const chart = new ApexCharts(chartEl, {
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'inherit',
            toolbar: { show: false }
        },
        series: [{
            name: 'Leads',
            data: counts
        }],
        xaxis: {
            categories: labels,
            labels: {
                style: { fontWeight: 600 }
            }
        },
        yaxis: {
            forceNiceScale: true,
            labels: {
                formatter: function (value) {
                    return Math.round(value);
                }
            }
        },
        colors: ['#1a4d8f'],
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '42%',
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                colors: ['#0f172a'],
                fontSize: '14px',
                fontWeight: 700
            }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return value + ' lead' + (value === 1 ? '' : 's');
                }
            }
        },
        grid: {
            borderColor: '#e2e8f0'
        }
    });

    chart.render();
})();
</script>
@endif
@if($showLeadSourceReport && $sourceCounts->isNotEmpty())
<script>
(function () {
    const labels = @json($sourceCounts->keys()->values());
    const counts = @json($sourceCounts->values());
    const chartEl = document.querySelector('#source-chart');
    if (!chartEl) return;

    const chart = new ApexCharts(chartEl, {
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'inherit',
            toolbar: { show: false }
        },
        series: [{
            name: 'Customers',
            data: counts
        }],
        xaxis: {
            categories: labels,
            labels: { style: { fontWeight: 600 } }
        },
        yaxis: {
            forceNiceScale: true,
            labels: {
                formatter: function (value) {
                    return Math.round(value);
                }
            }
        },
        colors: ['#10b981'],
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '42%',
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                colors: ['#0f172a'],
                fontSize: '14px',
                fontWeight: 700
            }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return value + ' customer' + (value === 1 ? '' : 's');
                }
            }
        },
        grid: { borderColor: '#e2e8f0' }
    });

    chart.render();
})();
</script>
@endif
<script>
(function () {
    const statuses = @json($statusCounts->keys()->values());
    const counts = @json($statusCounts->values());
    const customersUrl = @json(route('customers.index'));
    const palette = [
        '#1a4d8f', '#e2231a', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6',
        '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#64748b', '#06b6d4'
    ];

    document.querySelectorAll('.status-breakdown-item__dot').forEach((el, i) => {
        el.style.background = palette[i % palette.length];
    });

    const statusChartEl = document.querySelector('#status-chart');
    if (statusChartEl) {
    const chart = new ApexCharts(statusChartEl, {
        chart: {
            type: 'donut',
            height: 340,
            fontFamily: 'inherit',
            events: {
                dataPointSelection: function (event, ctx, config) {
                    const status = statuses[config.dataPointIndex];
                    if (status) {
                        window.location = customersUrl + '?status=' + encodeURIComponent(status);
                    }
                },
                legendClick: function (chartContext, seriesIndex) {
                    const status = statuses[seriesIndex];
                    if (status) {
                        window.location = customersUrl + '?status=' + encodeURIComponent(status);
                    }
                    return false;
                }
            }
        },
        series: counts,
        labels: statuses,
        colors: palette,
        legend: {
            position: 'bottom',
            fontSize: '15px',
            fontWeight: 600,
            formatter: function (seriesName, opts) {
                return seriesName + ': ' + opts.w.globals.series[opts.seriesIndex];
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '62%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                return opts.w.config.series[opts.seriesIndex];
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + ' customer' + (val === 1 ? '' : 's');
                }
            }
        },
        states: {
            hover: { filter: { type: 'darken', value: 0.85 } }
        }
    });
    chart.render();
    }
})();
</script>
@if($processTimelineCounts->isNotEmpty())
<script>
(function () {
    const labels = @json($processTimelineCounts->keys()->values());
    const counts = @json($processTimelineCounts->values());
    const customersUrl = @json(route('customers.index'));
    const palette = [
        '#198754', '#1a4d8f', '#e2231a', '#0ea5e9', '#f59e0b', '#8b5cf6',
        '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#64748b', '#06b6d4'
    ];

    document.querySelectorAll('.process-breakdown-item__dot').forEach((el, i) => {
        el.style.background = palette[i % palette.length];
    });

    function openProcessStage(stage) {
        if (!stage) return;

        window.location = customersUrl + '?process_stage=' + encodeURIComponent(stage);
    }

    const chart = new ApexCharts(document.querySelector('#process-chart'), {
        chart: {
            type: 'donut',
            height: 340,
            fontFamily: 'inherit',
            events: {
                dataPointSelection: function (event, ctx, config) {
                    openProcessStage(labels[config.dataPointIndex]);
                },
                legendClick: function (chartContext, seriesIndex) {
                    openProcessStage(labels[seriesIndex]);
                    return false;
                }
            }
        },
        series: counts,
        labels: labels,
        colors: palette,
        legend: {
            position: 'bottom',
            fontSize: '15px',
            fontWeight: 600,
            formatter: function (seriesName, opts) {
                return seriesName + ': ' + opts.w.globals.series[opts.seriesIndex];
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '62%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                return opts.w.config.series[opts.seriesIndex];
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + ' case';
                }
            }
        }
    });
    chart.render();
})();
</script>
@endif
@endpush
@endif
