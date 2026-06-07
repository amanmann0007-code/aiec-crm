@extends('layouts.app')

@section('page-title', 'Google Chat Notifications')

@section('content')
<div class="card shadow-sm">
    <div class="card-header">Allowed Google Chat Notifications</div>
    <div class="card-body">
        <form method="POST" action="{{ route('google-chat-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                @foreach($options as $key => $option)
                    <div class="col-lg-6">
                        <label class="google-chat-option">
                            <input type="checkbox"
                                   name="enabled_types[]"
                                   value="{{ $key }}"
                                   class="form-check-input"
                                   {{ in_array($key, $enabledTypes, true) ? 'checked' : '' }}>
                            <span>
                                <strong>{{ $option['label'] }}</strong>
                                <small>{{ $option['description'] }}</small>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .google-chat-option {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        height: 100%;
        padding: .85rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        cursor: pointer;
    }
    .google-chat-option small {
        display: block;
        color: #64748b;
        margin-top: .2rem;
        line-height: 1.35;
    }
</style>
@endpush
