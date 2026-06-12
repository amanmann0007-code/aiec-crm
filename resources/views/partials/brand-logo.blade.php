@php($company = config('company'))
<a href="{{ route('dashboard') }}" class="aiec-brand text-decoration-none" title="{{ $company['crm_name'] }}">
    <img src="{{ asset($company['logo_path']) }}" alt="{{ $company['name'] }}" class="aiec-brand__logo">
</a>
