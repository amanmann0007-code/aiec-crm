@extends('layouts.app')

@section('page-title', 'Add Lead')

@section('content')
<div class="card shadow-sm col-lg-8">
    <div class="card-body">
        <p class="text-muted">Add a new lead with basic details. You will only see leads you create.</p>
        <form method="POST" action="{{ route('customers.store-telecaller') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone *</label>
                    <input name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Country *</label>
                    <select name="country" class="form-select @error('country') is-invalid @enderror" required>
                        <option value="">— Select —</option>
                        @foreach(config('crm.countries') as $code => $label)
                            <option value="{{ $code }}" {{ old('country') == $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Visa Type *</label>
                    <select name="visa_type" class="form-select @error('visa_type') is-invalid @enderror" required>
                        <option value="">— Select —</option>
                        @foreach(config('crm.visa_types') as $visa)
                            <option value="{{ $visa }}" {{ old('visa_type') == $visa ? 'selected' : '' }}>{{ $visa }}</option>
                        @endforeach
                    </select>
                    @error('visa_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(config('crm.telecaller_statuses') as $status)
                            <option value="{{ $status }}" {{ old('status', 'will visit') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Add Lead</button>
                <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
