@extends('layouts.app')

@section('title', 'Customer Entry')
@section('page-title', 'Customer Entry')

@section('content')
<div class="container py-4">
    <div class="mx-auto" style="max-width: 980px;">
        <div class="mb-3 text-center">
            @include('partials.brand-logo')
            <h4 class="mt-3 mb-1">Customer Entry Form</h4>
            <p class="text-muted mb-0">Fill your details and our team will review your application.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('public.customer-entry.store') }}" id="public-entry-form">
                    @csrf
                    @php $residenceDefault = old('residence_country', 'india'); @endphp

                    <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                        <h6 class="mb-3 text-primary">Personal Details</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Name *</label>
                                <input name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Phone *</label>
                                <input name="phone" class="form-control phone-input-highlight" value="{{ old('phone') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Passport Number</label>
                                <input name="passport" class="form-control" value="{{ old('passport') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input name="email" type="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">DOB</label>
                                <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">Gender</label>
                                <div class="d-flex gap-3 pt-1">
                                    @foreach(config('crm.genders') as $g)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="gender_{{ $g }}" value="{{ $g }}" {{ old('gender') == $g ? 'checked' : '' }}>
                                            <label class="form-check-label" for="gender_{{ $g }}">{{ ucfirst($g) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(config('crm.marital_statuses') as $ms)
                                        <option value="{{ $ms }}" {{ old('marital_status') == $ms ? 'selected' : '' }}>{{ ucfirst($ms) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Father / Spouse Name</label>
                                <input name="father_spouse_name" class="form-control" value="{{ old('father_spouse_name') }}">
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
                                <select name="visa_type" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach(config('crm.visa_types') as $visa)
                                        <option value="{{ $visa }}" {{ old('visa_type') == $visa ? 'selected' : '' }}>{{ $visa }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country (Visa) *</label>
                                <select name="country" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach(config('crm.countries') as $code => $label)
                                        <option value="{{ $code }}" {{ old('country') == $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 mb-3">
                        <h6 class="mb-3 text-primary">Qualification</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Qualification</label>
                                <select name="qualification" class="form-select">
                                    <option value="">Select</option>
                                    @foreach($qualifications as $q)
                                        <option value="{{ $q }}" {{ old('qualification') == $q ? 'selected' : '' }}>{{ $q }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Qualification Pass-out Year</label>
                                <input type="number" name="qualification_year" class="form-control" min="1950" max="{{ date('Y') + 1 }}" value="{{ old('qualification_year') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Score</label>
                                <input name="score" class="form-control" value="{{ old('score') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GAP</label>
                                <select name="gap_years" class="form-select">
                                    <option value="">Select</option>
                                    @foreach(config('crm.gap_options') as $gap)
                                        <option value="{{ $gap }}" {{ old('gap_years') == $gap ? 'selected' : '' }}>{{ $gap }}</option>
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
                                    <option value="no" {{ old('english_test', 'no') == 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('english_test') == 'yes' ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12 d-none" id="english-wrap">
                                <div class="row g-3 border rounded p-3 bg-light">
                                    <div class="col-md-3"><label class="form-label">Test Type</label><input name="test_type" class="form-control" value="{{ old('test_type') }}"></div>
                                    <div class="col-md-2"><label class="form-label">L</label><input name="listening" class="form-control" value="{{ old('listening') }}"></div>
                                    <div class="col-md-2"><label class="form-label">R</label><input name="reading" class="form-control" value="{{ old('reading') }}"></div>
                                    <div class="col-md-2"><label class="form-label">W</label><input name="writing" class="form-control" value="{{ old('writing') }}"></div>
                                    <div class="col-md-2"><label class="form-label">S</label><input name="speaking" class="form-control" value="{{ old('speaking') }}"></div>
                                    <div class="col-md-2"><label class="form-label">O</label><input name="overall" class="form-control" value="{{ old('overall') }}"></div>
                                    <div class="col-md-3"><label class="form-label">Expiry</label><input type="date" name="test_expiry" class="form-control" value="{{ old('test_expiry') }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 mb-3">
                        <h6 class="mb-3 text-primary">Previous Refusals</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Previous Refusal</label>
                                <select name="previous_refusal" id="previous_refusal" class="form-select">
                                    <option value="no" {{ old('previous_refusal', 'no') == 'no' ? 'selected' : '' }}>No</option>
                                    <option value="yes" {{ old('previous_refusal') == 'yes' ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                            <div class="col-md-8 d-none" id="refusal-wrap">
                                <label class="form-label">Refusal countries (comma separated)</label>
                                <input name="refusal_countries_input" id="refusal_countries_input" class="form-control" placeholder="UK, USA, Canada" value="{{ is_array(old('refusal_countries')) ? implode(', ', old('refusal_countries')) : '' }}">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Details</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const engTest = document.getElementById('english_test');
const engWrap = document.getElementById('english-wrap');
const prevRef = document.getElementById('previous_refusal');
const refusWrap = document.getElementById('refusal-wrap');
const form = document.getElementById('public-entry-form');

function toggleEnglish() { engWrap.classList.toggle('d-none', engTest.value !== 'yes'); }
function toggleRefusal() { refusWrap.classList.toggle('d-none', prevRef.value !== 'yes'); }

engTest.addEventListener('change', toggleEnglish);
prevRef.addEventListener('change', toggleRefusal);
toggleEnglish(); toggleRefusal();

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
