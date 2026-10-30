@props([
    'name',
    'tone' => 'emerald',
    'size' => null,
])

@php
    $initials = \App\Support\PortalData::initials($name);
    $sizeClass = $size ? 'avatar-' . $size : '';
@endphp

<span {{ $attributes->merge(['class' => trim('avatar ' . $sizeClass . ' avatar-' . $tone)]) }} aria-hidden="true">{{ $initials }}</span>
