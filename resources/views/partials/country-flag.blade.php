@php
    $code = $code ?? '';
    $label = $label ?? (config('crm.countries')[$code] ?? $code);
@endphp
@if($code)
    <span class="country-flag-inline">
        <span>{{ $label }}</span>
    </span>
@else
    {{ $label ?: '—' }}
@endif
