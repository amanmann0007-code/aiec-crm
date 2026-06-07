@extends('layouts.app')

@section('title', 'Login — AIEC Institute CRM')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/aiec-logo.png') }}" alt="AIEC Institute" class="login-brand__logo">
                </a>
                <p class="text-muted mt-3 mb-0">Customer Relationship Management</p>
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
                    <hr>
                    <small class="text-muted">Demo: admin@test.com / password (run <code>php artisan db:seed</code>)</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
