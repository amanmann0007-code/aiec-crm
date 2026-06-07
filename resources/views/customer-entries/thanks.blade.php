@extends('layouts.app')

@section('title', 'Details Submitted')
@section('page-title', 'Details Submitted')

@section('content')
<div class="container py-5">
    <div class="mx-auto text-center" style="max-width: 620px;">
        @include('partials.brand-logo')
        <div class="card shadow-sm mt-4">
            <div class="card-body py-5">
                <h4 class="mb-2">Thank you</h4>
                <p class="text-muted mb-0">Your details have been submitted. Our team will review them and contact you soon.</p>
            </div>
        </div>
    </div>
</div>
@endsection
