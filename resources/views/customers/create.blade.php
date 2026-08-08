@extends('layouts.app')

@section('page-title', 'Add Customer')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('customers.store') }}" id="customer-form">
            @csrf
            @php
                $isAgentUser = auth()->user()->role === 'agent';
                $lead = $prefillLead ?? null;
                $entry = $prefillEntry ?? null;
                $residenceDefault = old('residence_country', optional($entry)->residence_country ?: 'india');
                $nameDefault = old('name', optional($lead)->name ?: optional($entry)->name);
                $phoneDefault = old('phone', optional($lead)->phone ?: optional($entry)->phone);
                $emailDefault = old('email', optional($entry)->email);
                $dobDefault = old('dob', optional($entry)->dob);
                $genderDefault = old('gender', optional($entry)->gender);
                $maritalDefault = old('marital_status', optional($entry)->marital_status);
                $fatherSpouseDefault = old('father_spouse_name', optional($entry)->father_spouse_name);
                $countryDefault = old('country', optional($lead)->country ?: optional($entry)->country);
                $visaTypeDefault = old('visa_type', optional($lead)->visa_type ?: optional($entry)->visa_type);
                $qualificationDefault = old('qualification', optional($entry)->qualification);
                $qualificationYearDefault = old('qualification_year', optional($entry)->qualification_year);
                $scoreDefault = old('score', optional($entry)->score);
                $gapDefault = old('gap_years', optional($entry)->gap_years);
                $englishTestDefault = old('english_test', optional($entry)->english_test ?: 'no');
                $englishSubjectScoreDefault = old('english_subject_score');
                $testTypeDefault = old('test_type', optional($entry)->test_type);
                $listeningDefault = old('listening', optional($entry)->listening);
                $readingDefault = old('reading', optional($entry)->reading);
                $writingDefault = old('writing', optional($entry)->writing);
                $speakingDefault = old('speaking', optional($entry)->speaking);
                $overallDefault = old('overall', optional($entry)->overall);
                $testExpiryDefault = old('test_expiry', optional($entry)->test_expiry);
                $previousRefusalDefault = old('previous_refusal', optional($entry)->previous_refusal ?: 'no');
                $refusalCountriesDefault = old('refusal_countries', optional($entry)->refusal_countries ?: []);
                $refusalText = is_array($refusalCountriesDefault) ? implode(', ', $refusalCountriesDefault) : '';
                $sourceDefault = old('source', $isAgentUser ? 'Agents' : ($lead ? 'Telecaller' : ($entry ? 'Online' : null)));
                $telecallerDefault = old('telecaller_id', optional($lead)->telecaller_id);
                $agentDefault = old('agent_id', $isAgentUser ? auth()->id() : null);
            @endphp
            @if($lead)
                <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                <div class="alert alert-info py-2">
                    Completing visiting client <strong>{{ $lead->pid }}</strong>. Saving this form will move it to assigned customers.
                </div>
            @endif
            @if($entry)
                <input type="hidden" name="entry_id" value="{{ $entry->id }}">
                <div class="alert alert-info py-2">
                    Reviewing tab entry from <strong>{{ $entry->name }}</strong>. Assign a counselor before saving.
                </div>
            @endif

            <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                <h6 class="mb-3 text-primary">Personal Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name *</label>
                        <input name="name" class="form-control" value="{{ $nameDefault }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone *</label>
                        <input name="phone" class="form-control phone-input-highlight @error('phone') is-invalid @enderror" value="{{ $phoneDefault }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ $emailDefault }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">DOB</label>
                        <input type="date" name="dob" class="form-control" value="{{ $dobDefault }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">Gender</label>
                        <div class="d-flex gap-3 pt-1">
                            @foreach(config('crm.genders') as $g)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_{{ $g }}" value="{{ $g }}" {{ $genderDefault == $g ? 'checked' : '' }}>
                                    <label class="form-check-label" for="gender_{{ $g }}">{{ ucfirst($g) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(config('crm.marital_statuses') as $ms)
                                <option value="{{ $ms }}" {{ $maritalDefault == $ms ? 'selected' : '' }}>{{ ucfirst($ms) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father / Spouse Name</label>
                        <input name="father_spouse_name" class="form-control" value="{{ $fatherSpouseDefault }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Current Country of Residence</label>
                        <select name="residence_country" class="form-select">
                            @foreach(config('crm.countries') as $code => $label)
                                <option value="{{ $code }}" {{ $residenceDefault === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Visa Type *</label>
                        <select name="visa_type" class="form-select @error('visa_type') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach(config('crm.visa_types') as $visa)
                                <option value="{{ $visa }}" {{ $visaTypeDefault == $visa ? 'selected' : '' }}>{{ $visa }}</option>
                            @endforeach
                        </select>
                        @error('visa_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Country (Visa) *</label>
                        <select name="country" class="form-select @error('country') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach(config('crm.countries') as $code => $label)
                                <option value="{{ $code }}" {{ $countryDefault == $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">Qualification</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Qualification</label>
                        <select name="qualification" class="form-select">
                            <option value="">—</option>
                            @foreach($qualifications as $q)
                                <option value="{{ $q }}" {{ $qualificationDefault == $q ? 'selected' : '' }}>{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Qualification Pass-out Year</label>
                        <input type="number" name="qualification_year" class="form-control" min="1950" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}" value="{{ $qualificationYearDefault }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Score</label>
                        <input name="score" class="form-control" value="{{ $scoreDefault }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GAP</label>
                        <select name="gap_years" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(config('crm.gap_options') as $gap)
                                <option value="{{ $gap }}" {{ $gapDefault == $gap ? 'selected' : '' }}>{{ $gap }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">English</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">English Test</label>
                        <select name="english_test" id="english_test" class="form-select">
                            <option value="no" {{ $englishTestDefault == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ $englishTestDefault == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="english-subject-score-wrap">
                        <label class="form-label">English Subject Score *</label>
                        <input name="english_subject_score"
                               id="english_subject_score"
                               class="form-control @error('english_subject_score') is-invalid @enderror"
                               value="{{ $englishSubjectScoreDefault }}"
                               placeholder="Example: 75%">
                        @error('english_subject_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 d-none" id="english-wrap">
                        <div class="row g-3 border rounded p-3 bg-light">
                            <div class="col-md-3"><label class="form-label">Test Type</label><input name="test_type" class="form-control" value="{{ $testTypeDefault }}"></div>
                            <div class="col-md-2"><label class="form-label">L</label><input name="listening" class="form-control" value="{{ $listeningDefault }}"></div>
                            <div class="col-md-2"><label class="form-label">R</label><input name="reading" class="form-control" value="{{ $readingDefault }}"></div>
                            <div class="col-md-2"><label class="form-label">W</label><input name="writing" class="form-control" value="{{ $writingDefault }}"></div>
                            <div class="col-md-2"><label class="form-label">S</label><input name="speaking" class="form-control" value="{{ $speakingDefault }}"></div>
                            <div class="col-md-2"><label class="form-label">O</label><input name="overall" class="form-control" value="{{ $overallDefault }}"></div>
                            <div class="col-md-3"><label class="form-label">Expiry</label><input type="date" name="test_expiry" class="form-control" value="{{ $testExpiryDefault }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">Source & Assignment</h6>
                <div class="row g-3">
                    @if($isAgentUser)
                        <input type="hidden" name="source" value="Agents">
                        <input type="hidden" name="agent_id" value="{{ auth()->id() }}">
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <input class="form-control" value="Agents" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agent</label>
                            <input class="form-control" value="{{ auth()->user()->name }}" readonly>
                        </div>
                    @else
                    @if($lead)
                        <input type="hidden" name="source" value="Telecaller">
                        <input type="hidden" name="telecaller_id" value="{{ $telecallerDefault }}">
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <input class="form-control" value="Telecaller" readonly>
                        </div>
                    @else
                    <div class="col-md-4">
                        <label class="form-label">Source</label>
                        <select name="source" id="source" class="form-select">
                            <option value="">—</option>
                            @foreach(['Walk-in','Reference','Agents','Online','Other'] as $s)
                                <option value="{{ $s }}" {{ $sourceDefault == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-4 d-none" id="reference-wrap">
                        <label class="form-label">Reference Name</label>
                        <input name="reference_name" class="form-control" value="{{ old('reference_name') }}">
                    </div>
                    <div class="col-md-4 d-none" id="agent-wrap">
                        <label class="form-label">Agent</label>
                        <select name="agent_id" id="agent_id" class="form-select @error('agent_id') is-invalid @enderror">
                            <option value="">â€”</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ (string) $agentDefault === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }}{{ $agent->agent_branch ? ' - ' . $agent->agent_branch : '' }}</option>
                            @endforeach
                        </select>
                        @error('agent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                    @unless($isAgentUser)
                    <div class="col-md-4" id="counselor-wrap">
                        <label class="form-label">Assign Counselor <span class="text-danger counselor-required-marker">*</span></label>
                        <select name="assigned_counselor_id" id="assigned_counselor_id" class="form-select @error('assigned_counselor_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach($counselors as $c)
                                <option value="{{ $c->id }}" {{ old('assigned_counselor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_counselor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endunless
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">Previous Refusals</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Previous Refusal</label>
                        <select name="previous_refusal" id="previous_refusal" class="form-select">
                            <option value="no" {{ $previousRefusalDefault == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ $previousRefusalDefault == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-none" id="refusal-wrap">
                        <label class="form-label">Refusal countries (comma separated)</label>
                        <input name="refusal_countries_input" id="refusal_countries_input" class="form-control" placeholder="UK, USA, Canada" value="{{ $refusalText }}">
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const source = document.getElementById('source');
const refWrap = document.getElementById('reference-wrap');
const agentWrap = document.getElementById('agent-wrap');
const agentSelect = document.getElementById('agent_id');
const counselorWrap = document.getElementById('counselor-wrap');
const counselorSelect = document.getElementById('assigned_counselor_id');
const engTest = document.getElementById('english_test');
const engWrap = document.getElementById('english-wrap');
const engSubjectWrap = document.getElementById('english-subject-score-wrap');
const engSubjectScore = document.getElementById('english_subject_score');
const prevRef = document.getElementById('previous_refusal');
const refusWrap = document.getElementById('refusal-wrap');
const form = document.getElementById('customer-form');

function toggleSource() {
    if (refWrap && source) {
        refWrap.classList.toggle('d-none', source.value !== 'Reference');
    }
    if (agentWrap && source) {
        const isAgentSource = source.value === 'Agents';
        agentWrap.classList.toggle('d-none', !isAgentSource);
        if (agentSelect) {
            agentSelect.required = isAgentSource;
            agentSelect.disabled = !isAgentSource;
            if (!isAgentSource) {
                agentSelect.value = '';
            }
        }
    }
    if (counselorWrap && counselorSelect && source) {
        const isAgentSource = source.value === 'Agents';
        counselorWrap.classList.toggle('d-none', isAgentSource);
        counselorSelect.required = !isAgentSource;
        counselorSelect.disabled = isAgentSource;
        if (isAgentSource) {
            counselorSelect.value = '';
        }
    }
}
function toggleEnglish() {
    const hasEnglishTest = engTest.value === 'yes';
    engWrap.classList.toggle('d-none', !hasEnglishTest);
    engSubjectWrap.classList.toggle('d-none', hasEnglishTest);
    if (engSubjectScore) {
        engSubjectScore.required = !hasEnglishTest;
        if (hasEnglishTest) {
            engSubjectScore.value = '';
        }
    }
}
function toggleRefusal() { refusWrap.classList.toggle('d-none', prevRef.value !== 'yes'); }

if (source) source.addEventListener('change', toggleSource);
engTest.addEventListener('change', toggleEnglish);
prevRef.addEventListener('change', toggleRefusal);
toggleSource(); toggleEnglish(); toggleRefusal();

form.addEventListener('submit', function () {
    const inp = document.getElementById('refusal_countries_input');
    if (inp && inp.value && prevRef.value === 'yes') {
        inp.value.split(',').map(s => s.trim()).filter(Boolean).forEach((country, i) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `refusal_countries[${i}]`;
            hidden.value = country;
            form.appendChild(hidden);
        });
    }
});
</script>
@endpush
