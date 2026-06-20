@extends('layouts.app')

@section('title', 'Login - ' . config('company.crm_name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="text-center mb-4">
                <p class="text-muted mb-0">{{ config('company.tagline', 'Customer Relationship Management') }}</p>
            </div>
            <div class="card shadow">
                <div class="card-body p-4">
                    <h5 class="mb-3">Sign in</h5>
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" style="background-color: #1a4d8f; border-color: #1a4d8f;">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
