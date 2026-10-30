@props(['status'])

@php
    $tone = \App\Support\PortalData::toneForStatus($status);
@endphp

<span {{ $attributes->merge(['class' => 'status-badge status-' . $tone]) }}>{{ $status }}</span>
