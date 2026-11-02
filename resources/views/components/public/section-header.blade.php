@props([
    'eyebrow',
    'title',
    'description' => null,
    'align' => 'left',
])

@php
    $centered = $align === 'center';
@endphp

<div {{ $attributes->merge(['class' => $centered ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl']) }}>
    <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e] {{ $centered ? 'justify-center' : '' }}">
        <span class="h-px w-8 bg-[#c9a45c]" aria-hidden="true"></span>
        <span>{{ $eyebrow }}</span>
    </div>

    <h2 class="mt-4 text-3xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[40px] sm:leading-[1.15]">
        {{ $title }}
    </h2>

    @if ($description)
        <p class="mt-4 max-w-xl text-sm leading-7 text-[#6b7873] {{ $centered ? 'mx-auto' : '' }}">
            {{ $description }}
        </p>
    @endif
</div>
