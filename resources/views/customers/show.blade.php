@extends('layouts.app')

@section('page-title', $customer->pid . ' — ' . $customer->name . ' — ' . $customer->country . ' — ' . $customer->visa_type . ' — ' . optional($customer->counselor)->name . ' — ' . optional($customer->telecaller)->name)

@section('content')
@if($canViewProcessTimeline)
    <div class="card shadow-sm mb-3 process-timeline-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Process Timeline</span>
            <span class="small text-muted">{{ $customer->visa_type ?? 'No visa type selected' }}</span>
        </div>
        <div class="card-body">
            @if(count($processTimeline))
                @php
                    $totalTimelineSteps = count($processTimeline);
                    $completedTimelineCount = collect($processTimeline)
                        ->filter(fn ($step) => $completedProcessSteps->has($step['key']))
                        ->count();
                    $timelineProgressPercent = $totalTimelineSteps ? round(($completedTimelineCount / $totalTimelineSteps) * 100) : 0;
                @endphp
                <div class="process-timeline-progress {{ $timelineProgressPercent ? '' : 'no-progress' }}"
                     data-total-steps="{{ $totalTimelineSteps }}"
                     style="--process-progress: {{ $timelineProgressPercent }}%;">
                    <div class="process-progress-track" aria-hidden="true">
                        <span class="process-progress-fill">
                            <span class="process-progress-arrow"><i class="bi bi-arrow-right"></i></span>
                        </span>
                    </div>
                    <div class="process-timeline-grid">
                    @foreach($processTimeline as $step)
                        @php
                            $completed = $completedProcessSteps->get($step['key']);
                            $isCompleted = (bool) $completed;
                            $completeUrl = route('customers.process-steps.complete', [$customer, $step['key']]);
                            $canReopenThisStep = $canReopenProcessTimeline
                                || (
                                    auth()->user()->role === 'counselor'
                                    && $customer->assigned_counselor_id === auth()->id()
                                    && $step['key'] === \App\Services\ProcessTimelineService::DROPOUT_KEY
                                );
                        @endphp

                        <button type="button"
                                class="process-step-tile {{ $isCompleted ? 'complete' : '' }}"
                                data-complete-url="{{ $completeUrl }}"
                                data-step-key="{{ $step['key'] }}"
                                data-completed="{{ $isCompleted ? '1' : '0' }}"
                                data-can-reopen="{{ $canReopenThisStep ? '1' : '0' }}"
                                data-confirm-complete="{{ auth()->user()->role === 'counselor' ? '1' : '0' }}"
                                {{ (!$canCompleteProcessTimeline || ($isCompleted && !$canReopenThisStep)) ? 'disabled' : '' }}>
                            <span class="process-step-index">{{ $step['order'] }}</span>
                            <span class="process-step-label">{{ $step['label'] }}</span>
                            <span class="process-step-state">
                                @if($isCompleted)
                                    <i class="bi bi-check-lg"></i>
                                    {{ optional($completed->completed_at)->format('d M Y') }}
                                @else
                                    Pending
                                @endif
                            </span>
                        </button>
                    @endforeach
                    </div>
                </div>
            @else
                <div class="text-muted small">No process timeline configured for this visa type yet.</div>
            @endif
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Customer Info</div>
            <div class="card-body small">
                @if(in_array(auth()->user()->role, ['admin', 'counselor'], true))
                    <div class="mb-2">
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit Customer</a>
                    </div>
                @endif
                <p><strong>PID:</strong> {{ $customer->pid }}</p>
                <p><strong>Phone:</strong> {{ $customer->phone }}</p>
                <p><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
                <p><strong>DOB:</strong> {{ $customer->dob ?? '—' }}</p>
                <p><strong>Gender:</strong> {{ $customer->gender ? ucfirst($customer->gender) : '—' }}</p>
                <p><strong>Marital Status:</strong> {{ $customer->marital_status ? ucfirst($customer->marital_status) : '—' }}</p>
                <p><strong>Father / Spouse:</strong> {{ $customer->father_spouse_name ?? '—' }}</p>
                <p><strong>Residence:</strong>
                    @if($customer->residence_country)
                        @include('partials.country-flag', ['code' => $customer->residence_country])
                    @else
                        —
                    @endif
                </p>
                <p><strong>Country (visa):</strong>
                    @if($customer->country)
                        @include('partials.country-flag', ['code' => $customer->country])
                    @else
                        —
                    @endif
                </p>
                <p><strong>Visa:</strong> {{ $customer->visa_type ?? '—' }}</p>
                <p><strong>Status:</strong> <span class="badge bg-primary" id="customer-status-badge">{{ $customer->status }}</span></p>
                @if(auth()->user()->role !== 'agent')
                <p><strong>Counselor:</strong> {{ optional($customer->counselor)->name ?? '—' }}</p>
                <p><strong>Telecaller:</strong> {{ optional($customer->telecaller)->name ?? '—' }}</p>
                @endif
                <p><strong>Qualification:</strong> {{ $customer->qualification ?? '—' }}</p>
                <p><strong>Pass-out Year:</strong> {{ $customer->qualification_year ?? '—' }}</p>
                <p><strong>GAP:</strong> {{ $customer->gap_years ?? '—' }}</p>
                <p><strong>English Test:</strong> {{ ucfirst($customer->english_test ?? 'no') }}</p>
                @if(($customer->english_test ?? 'no') === 'no')
                    <p><strong>English Subject Score:</strong> {{ $customer->english_subject_score ?? '—' }}</p>
                @else
                    <p><strong>English Exam:</strong> {{ $customer->test_type ?? '—' }}</p>
                @endif
                @if($customer->refusals->count())
                    <p><strong>Refusals:</strong> {{ $customer->refusals->pluck('country')->join(', ') }}</p>
                @endif
            </div>
        </div>

        @if($canViewAgentCommercial)
        <div class="card shadow-sm mt-3">
            <div class="card-header">Agent Commercial Details</div>
            <div class="card-body">
                <p><strong>Agent:</strong> {{ optional($customer->agent)->name ?? '--' }}</p>
                @if($canManageAgentCommercial)
                    <form method="POST" action="{{ route('customers.agent-commercial.update', $customer) }}">
                        @csrf
                        <div class="mb-2">
                            <label for="visa_duration" class="form-label small">Visa duration</label>
                            <input type="text" name="visa_duration" id="visa_duration" class="form-control form-control-sm @error('visa_duration') is-invalid @enderror" value="{{ old('visa_duration', $customer->visa_duration) }}">
                            @error('visa_duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="actual_cost" class="form-label small">Actual cost</label>
                                <input type="number" name="actual_cost" id="actual_cost" class="form-control form-control-sm @error('actual_cost') is-invalid @enderror" min="0" step="0.01" value="{{ old('actual_cost', $customer->actual_cost) }}">
                                @error('actual_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="b2b_cost" class="form-label small">B2B cost</label>
                                <input type="number" name="b2b_cost" id="b2b_cost" class="form-control form-control-sm @error('b2b_cost') is-invalid @enderror" min="0" step="0.01" value="{{ old('b2b_cost', $customer->b2b_cost) }}">
                                @error('b2b_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mt-2 small">
                            <strong>Margin:</strong>
                            {{ $customer->margin !== null ? number_format((float) $customer->margin, 2) : '--' }}
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mt-3">Save Commercial Details</button>
                    </form>
                @else
                    <p><strong>Visa duration:</strong> {{ $customer->visa_duration ?? '--' }}</p>
                    <p><strong>B2B cost:</strong> {{ $customer->b2b_cost !== null ? number_format((float) $customer->b2b_cost, 2) : '--' }}</p>
                    @if(auth()->user()->role !== 'agent')
                    <p><strong>Actual cost:</strong> {{ $customer->actual_cost !== null ? number_format((float) $customer->actual_cost, 2) : '--' }}</p>
                    <p class="mb-0"><strong>Margin:</strong> {{ $customer->margin !== null ? number_format((float) $customer->margin, 2) : '--' }}</p>
                    @endif
                @endif
            </div>
        </div>
        @endif

        <div class="card shadow-sm mt-3">
            <div class="card-header">Intake</div>
            <div class="card-body">
                @php
                    $intakeMonths = [
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
                    ];
                    $currentIntake = $customer->intake_month && $customer->intake_year
                        ? ($intakeMonths[$customer->intake_month] ?? $customer->intake_month) . ' ' . $customer->intake_year
                        : null;
                @endphp

                @if($currentIntake)
                    <div class="mb-3">
                        <span class="badge bg-primary fs-6">{{ $currentIntake }}</span>
                    </div>
                @else
                    <div class="text-muted small mb-3">No intake set.</div>
                @endif

                @if($canManageIntake)
                    <form method="POST" action="{{ route('customers.intake.update', $customer) }}" class="intake-form">
                        @csrf
                        <div class="intake-field">
                            <label for="intake_month" class="form-label small">Month</label>
                            <select name="intake_month" id="intake_month" class="form-select form-select-sm @error('intake_month') is-invalid @enderror" required>
                                <option value="">Select</option>
                                @foreach($intakeMonths as $monthNumber => $monthLabel)
                                    <option value="{{ $monthNumber }}" {{ (string) old('intake_month', $customer->intake_month) === (string) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                            @error('intake_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="intake-field">
                            <label for="intake_year" class="form-label small">Year</label>
                            <input type="number"
                                   name="intake_year"
                                   id="intake_year"
                                   class="form-control form-control-sm @error('intake_year') is-invalid @enderror"
                                   min="{{ now()->year }}"
                                   max="{{ now()->year + 20 }}"
                                   value="{{ old('intake_year', $customer->intake_year ?: now()->year) }}"
                                   required>
                            @error('intake_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="intake-action">
                            <label class="form-label small intake-action-label">Save</label>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header">Documents</div>
            <div class="card-body">
                <form method="POST" action="{{ route('customers.documents.store', $customer) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label for="document_name" class="form-label small">Document name *</label>
                        <input type="text" name="document_name" id="document_name" class="form-control form-control-sm @error('document_name') is-invalid @enderror" value="{{ old('document_name') }}" placeholder="Example: Passport, IELTS scorecard, Offer letter" required>
                        @error('document_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <label for="document-upload-input" class="document-drop-zone mb-2" id="document-drop-zone">
                        <input type="file" name="documents[]" id="document-upload-input" class="document-upload-input @error('documents') is-invalid @enderror @error('documents.*') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" multiple required>
                        <span class="document-drop-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                        <span class="document-drop-title">Drop files here or click to upload</span>
                        <span class="document-drop-hint">Allowed: JPG, JPEG, PNG, PDF. Max 20 MB per file.</span>
                        <span class="document-file-list text-muted" id="document-file-list">No files selected</span>
                    </label>
                    @error('documents')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    @error('documents.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <button class="btn btn-sm btn-outline-primary">Upload</button>
                </form>
                <ul class="list-group list-group-flush mt-3">
                    @forelse($customer->documents as $doc)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">{{ $doc->document_name }}</a>
                            @if(auth()->user()->role === 'admin')
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="text-muted">No documents</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($canViewSpecialRemark)
            <div class="card shadow-sm mb-3 special-remark-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Special Remark</span>
                    <span class="small text-muted">Counselor only entry</span>
                </div>
                <div class="card-body">
                    @if($customer->special_remark)
                        <div class="special-remark-box">
                            {!! nl2br(e($customer->special_remark)) !!}
                        </div>
                        <div class="small text-muted mt-2">
                            Added by {{ optional($customer->specialRemarkAuthor)->name ?? 'Unknown' }}
                            @if($customer->special_remark_at)
                                &middot; {{ $customer->special_remark_at->format('d M Y h:i A') }}
                            @endif
                        </div>
                    @elseif($canAddSpecialRemark)
                        <form method="POST" action="{{ route('customers.special-remark.store', $customer) }}">
                            @csrf
                            <div class="mb-2">
                                <textarea name="special_remark"
                                          class="form-control @error('special_remark') is-invalid @enderror"
                                          rows="3"
                                          maxlength="5000"
                                          placeholder="Enter special remark..."
                                          required>{{ old('special_remark') }}</textarea>
                                @error('special_remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Save Special Remark</button>
                        </form>
                    @else
                        <div class="text-muted small">No special remark entered.</div>
                    @endif
                </div>
            </div>
        @endif

        <div class="card shadow-sm mb-3 remarks-card">
            <div class="card-header">Remarks (Chat)</div>
            <div class="card-body">
                <div class="chat-box mb-3">
                    @forelse($customer->remarks as $remark)
                        <div class="chat-bubble {{ $remark->user_id === auth()->id() ? 'mine' : '' }}">
                            <div class="small text-muted">{{ optional($remark->user)->name }} · {{ $remark->created_at }}</div>
                            <div>
                                @php
                                    $msg = e($remark->message);
                                    foreach ($remark->taggedUsers as $tagged) {
                                        $fullNameMention = '@' . $tagged->name;
                                        $msg = str_replace(e($fullNameMention), '<span class="mention-highlight">' . e($fullNameMention) . '</span>', $msg);

                                        foreach ($tagged->mentionAliases() as $alias) {
                                            $pattern = '/@' . preg_quote(e($alias), '/') . '\b/i';
                                            $msg = preg_replace($pattern, '<span class="mention-highlight">$0</span>', $msg);
                                        }
                                    }
                                @endphp
                                {!! nl2br($msg) !!}
                            </div>
                            @if($remark->status_update)
                                <div class="small mt-1"><span class="badge bg-info">Status: {{ $remark->status_update }}</span></div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No remarks yet.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('customers.remarks.store', $customer) }}" id="remark-form">
                    @csrf
                    <div class="mb-2 mention-wrap">
                        <div class="mention-dropdown d-none"></div>
                        <textarea name="message" id="remark-message" class="form-control" rows="2" placeholder="Type remark... use @ to tag someone" required autocomplete="off">{{ old('message') }}</textarea>
                        <div class="tagged-user-ids"></div>
                    </div>
                    <div class="remark-actions-row">
                        <div class="remark-col remark-col-status">
                            <label for="status_update" class="form-label small">Select status</label>
                            <select name="status_update" id="status_update" class="form-select remark-field-input">
                                <option value="">—</option>
                                @foreach(config('crm.remark_statuses', []) as $st)
                                    <option value="{{ $st }}" {{ old('status_update') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                            <small class="field-hint text-muted" aria-hidden="true">&nbsp;</small>
                        </div>
                        <div class="remark-col remark-col-followup">
                            <label for="follow_up_date" class="form-label small">Next follow up</label>
                            <input type="date" name="follow_up_date" id="follow_up_date" class="form-control remark-field-input"
                                   value="{{ old('follow_up_date', now()->addDay()->format('Y-m-d')) }}">
                            <small id="follow-up-hint" class="field-hint text-muted">Date only — defaults to tomorrow</small>
                        </div>
                        <div class="remark-col remark-col-send">
                            <label class="form-label small remark-send-label">Send</label>
                            <button type="submit" class="btn btn-primary remark-field-input w-100">Send</button>
                            <small class="field-hint text-muted" aria-hidden="true">&nbsp;</small>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header">Filing by</div>
            <div class="card-body">
                <form method="POST" action="{{ route('customers.filing-by.update', $customer) }}">
                    @csrf
                    <div class="mb-2">
                        <textarea name="filing_by"
                                  class="form-control @error('filing_by') is-invalid @enderror"
                                  rows="3"
                                  maxlength="5000"
                                  placeholder="Enter filing by details...">{{ old('filing_by', $customer->filing_by) }}</textarea>
                        @error('filing_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Save Filing by</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header">Fees</div>
            <div class="card-body">
                @if($canAddFees)
                    <form method="POST" action="{{ route('customers.fees.store', $customer) }}" class="fees-entry-form mb-3">
                        @csrf
                        <div class="fees-entry-field">
                            <label for="fee_amount" class="form-label small">Amount</label>
                            <input type="number" name="amount" id="fee_amount" class="form-control form-control-sm @error('amount') is-invalid @enderror" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="fees-entry-field fees-purpose-field">
                            <label for="fee_purpose" class="form-label small">Purpose</label>
                            <input type="text" name="purpose" id="fee_purpose" class="form-control form-control-sm @error('purpose') is-invalid @enderror" value="{{ old('purpose') }}" placeholder="Example: Registration, Refund" required>
                            @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="fees-entry-action">
                            <label class="form-label small fees-entry-action-label">Add</label>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                        </div>
                    </form>
                @endif

                @php($feesTotal = $customer->fees->sum(fn ($fee) => $fee->signedAmount()))
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 fees-table">
                        <thead>
                            <tr>
                                <th>Purpose</th>
                                <th>Added By</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($customer->fees as $fee)
                            @php($isRefund = $fee->isRefund())
                            <tr class="{{ $isRefund ? 'fee-refund-row' : '' }}">
                                <td>{{ $isRefund ? '- ' : '' }}{{ $fee->purpose }}</td>
                                <td>{{ optional($fee->user)->name ?? '--' }}</td>
                                <td>{{ optional($fee->created_at)->format('d M Y') }}</td>
                                <td class="text-end fw-semibold">
                                    {{ $isRefund ? '- ' : '' }}{{ number_format((float) $fee->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No fees added yet.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">
                                    @if($canAddFees)
                                        <a href="{{ route('customers.fees.receipt', $customer) }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-printer"></i> Print Fee Receipt
                                        </a>
                                    @endif
                                </th>
                                <th class="text-end">Total</th>
                                <th class="text-end">{{ number_format($feesTotal, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if(auth()->user()->role === 'admin')
        <div class="card shadow-sm">
            <div class="card-header">Activity Log</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($activityLogs as $log)
                        <li class="list-group-item small">
                            <strong>{{ $log->action_type }}</strong> — {{ $log->display_description }}
                            <span class="text-muted">· {{ optional($log->user)->name }} · {{ $log->created_at }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.remark-actions-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: .75rem;
}
.remarks-card .card-body {
    display: flex;
    flex-direction: column;
}
.remarks-card #remark-form {
    order: 1;
}
.remarks-card .chat-box {
    order: 2;
    margin-top: 1rem;
    margin-bottom: 0 !important;
}
.remark-col {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.remark-col-status { flex: 1.4 1 180px; }
.remark-col-followup { flex: 1.1 1 160px; }
.remark-col-send { flex: 0 0 110px; }
.remark-col .form-label {
    margin-bottom: .35rem;
    line-height: 1.2;
    min-height: 1.2rem;
}
.remark-col-send .remark-send-label {
    visibility: hidden;
}
.remark-field-input {
    height: 38px;
    min-height: 38px;
}
.special-remark-card .card-header {
    background: #fff8e6;
}
.special-remark-box {
    border-left: 4px solid #f59f00;
    border-radius: 6px;
    background: #fffdf5;
    color: #1f2937;
    padding: .85rem 1rem;
    white-space: normal;
}
.remark-col .field-hint {
    display: block;
    min-height: 1.125rem;
    margin-top: .35rem;
    font-size: .75rem;
    line-height: 1.125rem;
}
.document-drop-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .3rem;
    min-height: 150px;
    padding: 1rem;
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    background: #f8fafc;
    color: #475569;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s;
}
.document-drop-zone.drag-over {
    border-color: var(--aiec-blue);
    background: #eef6ff;
    color: #1e40af;
}
.document-upload-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.document-drop-icon {
    font-size: 1.6rem;
    color: var(--aiec-blue);
}
.document-drop-title {
    font-weight: 700;
    color: #0f172a;
}
.document-drop-hint,
.document-file-list {
    font-size: .78rem;
}
.document-file-list {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fees-entry-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: .75rem;
}
.fees-entry-field {
    flex: 1 1 180px;
}
.fees-purpose-field {
    flex: 2 1 260px;
}
.fees-entry-action {
    flex: 0 0 110px;
}
.fees-entry-action-label {
    visibility: hidden;
}
.fees-table tfoot th {
    border-top: 2px solid #cbd5e1;
    background: #f8fafc;
}
.fee-refund-row td {
    color: #dc3545;
}
.intake-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: .75rem;
}
.intake-field {
    flex: 1 1 110px;
}
.intake-action {
    flex: 0 0 90px;
}
.intake-action-label {
    visibility: hidden;
}
.process-timeline-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(100deg, #ffffff, #eef6ff, #f3fbf7, #fff7ed, #ffffff);
    background-size: 300% 100%;
    animation: processTimelineGlow 8s ease-in-out infinite;
}
.process-timeline-card .card-header,
.process-timeline-card .card-body {
    position: relative;
    z-index: 1;
    background: transparent;
}
@keyframes processTimelineGlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.process-timeline-progress {
    position: relative;
    padding: .35rem 0 .15rem;
}
.process-progress-track {
    position: absolute;
    top: 31px;
    left: 56px;
    right: 56px;
    height: 8px;
    border-radius: 999px;
    background: #dbe3ec;
    overflow: hidden;
    z-index: 0;
}
.process-progress-fill {
    position: absolute;
    inset: 0 auto 0 0;
    width: var(--process-progress);
    min-width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #16a34a, #22c55e, #0ea5e9);
    transition: width .25s ease;
}
.process-progress-arrow {
    position: absolute;
    right: -11px;
    top: 50%;
    width: 22px;
    height: 22px;
    transform: translateY(-50%);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #0ea5e9;
    color: #fff;
    font-size: .8rem;
    box-shadow: 0 3px 8px rgba(14, 165, 233, .24);
}
.process-timeline-progress.no-progress .process-progress-arrow {
    display: none;
}
.process-timeline-grid {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    overflow-x: auto;
    padding: 0 .25rem .35rem;
}
.process-step-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .45rem;
    flex: 0 0 128px;
    min-height: 116px;
    border: 0;
    padding: 0 .25rem;
    background: transparent;
    color: #334155;
    text-align: center;
}
.process-step-tile:not(:disabled):hover {
    color: var(--aiec-blue);
}
.process-step-tile.complete {
    color: #146c43;
}
.process-step-tile:disabled {
    cursor: default;
    opacity: 1;
}
.process-step-index {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #fff;
    background: #dbe3ec;
    color: #475569;
    font-size: .95rem;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(15, 23, 42, .14);
    transition: background .2s, color .2s, transform .2s;
}
.process-step-tile:not(:disabled):hover .process-step-index {
    transform: translateY(-2px);
    background: #cfe8ff;
}
.process-step-tile.complete .process-step-index {
    background: #198754;
    color: #fff;
}
.process-step-label {
    font-weight: 600;
    line-height: 1.2;
    max-width: 128px;
    min-height: 2.35rem;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}
.process-step-state {
    font-size: .75rem;
    color: inherit;
    min-height: 1.2rem;
}
@media (max-width: 767.98px) {
    .remark-col { flex: 1 1 100%; }
    .remark-col-send { flex: 1 1 100%; }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/mention-autocomplete.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('remark-message');
    if (textarea) {
        initMentionAutocomplete(textarea, {
            searchUrl: '{{ route('users.mention-search') }}'
        });
    }

    const documentDropZone = document.getElementById('document-drop-zone');
    const documentUploadInput = document.getElementById('document-upload-input');
    const documentFileList = document.getElementById('document-file-list');

    function updateDocumentFileList() {
        if (!documentUploadInput || !documentFileList) return;

        const files = Array.from(documentUploadInput.files || []);
        documentFileList.textContent = files.length
            ? files.map(file => file.name).join(', ')
            : 'No files selected';
    }

    if (documentDropZone && documentUploadInput) {
        ['dragenter', 'dragover'].forEach(eventName => {
            documentDropZone.addEventListener(eventName, event => {
                event.preventDefault();
                documentDropZone.classList.add('drag-over');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            documentDropZone.addEventListener(eventName, event => {
                event.preventDefault();
                documentDropZone.classList.remove('drag-over');
            });
        });

        documentDropZone.addEventListener('drop', event => {
            documentUploadInput.files = event.dataTransfer.files;
            updateDocumentFileList();
        });

        documentUploadInput.addEventListener('change', updateDocumentFileList);
    }

    const statusSelect = document.getElementById('status_update');
    const followUpInput = document.getElementById('follow_up_date');
    const followUpHint = document.getElementById('follow-up-hint');
    const intakeForm = document.querySelector('.intake-form');
    const intakeMonth = document.getElementById('intake_month');
    const intakeYear = document.getElementById('intake_year');
    const requiresFollowUp = @json(config('crm.statuses_requiring_follow_up'));
    const noFollowUp = @json(config('crm.statuses_no_follow_up'));
    const tomorrowDefault = '{{ now()->addDay()->format('Y-m-d') }}';
    const currentMonth = {{ now()->month }};
    const currentYear = {{ now()->year }};

    function updateFollowUpRequirement() {
        if (!statusSelect || !followUpInput) return;

        const status = statusSelect.value;
        const isRequired = requiresFollowUp.includes(status);
        const isOptional = !status || noFollowUp.includes(status);

        followUpInput.required = isRequired;

        if (noFollowUp.includes(status)) {
            followUpInput.value = '';
            followUpInput.disabled = true;
            followUpHint.textContent = 'Not required for this status';
        } else {
            followUpInput.disabled = false;
            if (!followUpInput.value) {
                followUpInput.value = tomorrowDefault;
            }
            if (isRequired) {
                followUpHint.textContent = 'Required for this status';
            } else if (isOptional) {
                followUpHint.textContent = 'Date only — defaults to tomorrow';
            } else {
                followUpHint.textContent = 'Date only — defaults to tomorrow';
            }
        }
    }

    statusSelect?.addEventListener('change', updateFollowUpRequirement);
    updateFollowUpRequirement();

    intakeForm?.addEventListener('submit', function (event) {
        const month = Number(intakeMonth?.value || 0);
        const year = Number(intakeYear?.value || 0);
        if (year < currentYear || (year === currentYear && month < currentMonth)) {
            event.preventDefault();
            alert('Intake cannot be earlier than the current month.');
        }
    });

    function refreshTimelineProgress(tile) {
        const timeline = tile.closest('.process-timeline-progress');
        if (!timeline) return;

        const total = Number(timeline.dataset.totalSteps || 0);
        const completed = timeline.querySelectorAll('.process-step-tile[data-completed="1"]').length;
        const progress = total ? Math.round((completed / total) * 100) : 0;

        timeline.style.setProperty('--process-progress', progress + '%');
        timeline.classList.toggle('no-progress', progress === 0);
    }

    document.querySelectorAll('.process-step-tile:not(:disabled)').forEach((tile) => {
        tile.addEventListener('click', () => {
            const url = tile.dataset.completeUrl;
            const isCompleted = tile.dataset.completed === '1';
            const canReopen = tile.dataset.canReopen === '1';
            if (!url || (isCompleted && !canReopen)) {
                return;
            }

            if (!isCompleted && tile.dataset.confirmComplete === '1' && !confirm('Mark this process step as completed? This cannot be changed back by counselor.')) {
                return;
            }

            tile.disabled = true;
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Could not complete step');
                    }
                    return response.json();
                })
                .then((data) => {
                    const state = tile.querySelector('.process-step-state');
                    if (!data.completed) {
                        tile.classList.remove('complete');
                        tile.dataset.completed = '0';
                        if (state) {
                            state.textContent = 'Pending';
                        }
                        refreshTimelineProgress(tile);
                        tile.disabled = false;
                        return;
                    }

                    tile.classList.add('complete');
                    tile.dataset.completed = '1';
                    if (state) {
                        const date = data.completed_at ? data.completed_at.slice(0, 10) : 'Completed';
                        state.innerHTML = '<i class="bi bi-check-lg"></i> ' + date;
                    }
                    if (data.customer_status) {
                        const statusBadge = document.getElementById('customer-status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = data.customer_status;
                        }
                        if (statusSelect && data.customer_status === 'plan drop') {
                            statusSelect.value = 'plan drop';
                            updateFollowUpRequirement();
                        }
                    }
                    refreshTimelineProgress(tile);
                    tile.disabled = tile.dataset.canReopen !== '1';
                })
                .catch(() => {
                    tile.disabled = false;
                    alert('Could not complete this process step. Please try again.');
                });
        });
    });
});
</script>
@endpush
