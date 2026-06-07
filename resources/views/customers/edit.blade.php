@extends('layouts.app')

@section('page-title', 'Edit Customer')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('customers.update', $customer) }}" id="customer-form">
            @csrf
            @method('PUT')
            @php
                $residenceDefault = old('residence_country', $customer->residence_country ?: 'india');
                $refusalDefault = old('refusal_countries');
                $refusalText = is_array($refusalDefault) ? implode(', ', $refusalDefault) : implode(', ', $existingRefusalCountries);
            @endphp

            <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                <h6 class="mb-3 text-primary">Personal Details</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">PID</label>
                        <input class="form-control" value="{{ $customer->pid }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Name *</label>
                        <input name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Phone *</label>
                        <input name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $customer->email) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">DOB</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob', $customer->dob) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">Gender</label>
                        <div class="d-flex gap-3 pt-1">
                            @foreach(config('crm.genders') as $g)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_{{ $g }}" value="{{ $g }}" {{ old('gender', $customer->gender) == $g ? 'checked' : '' }}>
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
                                <option value="{{ $ms }}" {{ old('marital_status', $customer->marital_status) == $ms ? 'selected' : '' }}>{{ ucfirst($ms) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father / Spouse Name</label>
                        <input name="father_spouse_name" class="form-control" value="{{ old('father_spouse_name', $customer->father_spouse_name) }}">
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
                                <option value="{{ $visa }}" {{ old('visa_type', $customer->visa_type) == $visa ? 'selected' : '' }}>{{ $visa }}</option>
                            @endforeach
                        </select>
                        @error('visa_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Country (Visa) *</label>
                        <select name="country" class="form-select @error('country') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach(config('crm.countries') as $code => $label)
                                <option value="{{ $code }}" {{ old('country', $customer->country) == $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                <option value="{{ $q }}" {{ old('qualification', $customer->qualification) == $q ? 'selected' : '' }}>{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Qualification Pass-out Year</label>
                        <input type="number" name="qualification_year" class="form-control" min="1950" max="{{ date('Y') + 1 }}" value="{{ old('qualification_year', $customer->qualification_year) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Score</label>
                        <input name="score" class="form-control" value="{{ old('score', $customer->score) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GAP</label>
                        <select name="gap_years" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(config('crm.gap_options') as $gap)
                                <option value="{{ $gap }}" {{ old('gap_years', $customer->gap_years) == $gap ? 'selected' : '' }}>{{ $gap }}</option>
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
                            <option value="no" {{ old('english_test', $customer->english_test ?: 'no') == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ old('english_test', $customer->english_test) == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            @foreach(config('crm.customer_statuses', []) as $status)
                                <option value="{{ $status }}" {{ old('status', $customer->status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 d-none" id="english-wrap">
                        <div class="row g-3 border rounded p-3 bg-light">
                            <div class="col-md-3"><label class="form-label">Test Type</label><input name="test_type" class="form-control" value="{{ old('test_type', $customer->test_type) }}"></div>
                            <div class="col-md-2"><label class="form-label">L</label><input name="listening" class="form-control" value="{{ old('listening', $customer->listening) }}"></div>
                            <div class="col-md-2"><label class="form-label">R</label><input name="reading" class="form-control" value="{{ old('reading', $customer->reading) }}"></div>
                            <div class="col-md-2"><label class="form-label">W</label><input name="writing" class="form-control" value="{{ old('writing', $customer->writing) }}"></div>
                            <div class="col-md-2"><label class="form-label">S</label><input name="speaking" class="form-control" value="{{ old('speaking', $customer->speaking) }}"></div>
                            <div class="col-md-2"><label class="form-label">O</label><input name="overall" class="form-control" value="{{ old('overall', $customer->overall) }}"></div>
                            <div class="col-md-3"><label class="form-label">Expiry</label><input type="date" name="test_expiry" class="form-control" value="{{ old('test_expiry', $customer->test_expiry) }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">Source & Assignment</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Source</label>
                        <select name="source" id="source" class="form-select">
                            <option value="">—</option>
                            @foreach(['Walk-in','Reference','Telecaller','Online','Other'] as $s)
                                <option value="{{ $s }}" {{ old('source', $customer->source) == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-none" id="reference-wrap">
                        <label class="form-label">Reference Name</label>
                        <input name="reference_name" class="form-control" value="{{ old('reference_name', $customer->reference_name) }}">
                    </div>
                    <div class="col-md-4" id="telecaller-wrap">
                        <label class="form-label">Telecaller <span class="text-muted small">(required if no counselor)</span></label>
                        <select name="telecaller_id" class="form-select">
                            <option value="">—</option>
                            @foreach($telecallers as $t)
                                <option value="{{ $t->id }}" {{ old('telecaller_id', $customer->telecaller_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assign Counselor <span class="text-muted small">(required if no telecaller)</span></label>
                        <select name="assigned_counselor_id" class="form-select">
                            <option value="">—</option>
                            @foreach($counselors as $c)
                                <option value="{{ $c->id }}" {{ old('assigned_counselor_id', $customer->assigned_counselor_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mb-3">
                <h6 class="mb-3 text-primary">Previous Refusals</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Previous Refusal</label>
                        <select name="previous_refusal" id="previous_refusal" class="form-select">
                            <option value="no" {{ old('previous_refusal', $customer->previous_refusal ?: 'no') == 'no' ? 'selected' : '' }}>No</option>
                            <option value="yes" {{ old('previous_refusal', $customer->previous_refusal) == 'yes' ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    <div class="col-md-8 d-none" id="refusal-wrap">
                        <label class="form-label">Refusal countries (comma separated)</label>
                        <input name="refusal_countries_input" id="refusal_countries_input" class="form-control" placeholder="UK, USA, Canada" value="{{ $refusalText }}">
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const source = document.getElementById('source');
const refWrap = document.getElementById('reference-wrap');
const engTest = document.getElementById('english_test');
const engWrap = document.getElementById('english-wrap');
const prevRef = document.getElementById('previous_refusal');
const refusWrap = document.getElementById('refusal-wrap');
const form = document.getElementById('customer-form');

function toggleSource() {
    refWrap.classList.toggle('d-none', source.value !== 'Reference');
}
function toggleEnglish() { engWrap.classList.toggle('d-none', engTest.value !== 'yes'); }
function toggleRefusal() { refusWrap.classList.toggle('d-none', prevRef.value !== 'yes'); }

source.addEventListener('change', toggleSource);
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
