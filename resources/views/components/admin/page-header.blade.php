@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div class="page-heading">
    <div class="page-heading-main">
        @if ($eyebrow)
            <div class="page-eyebrow">{{ $eyebrow }}</div>
        @endif

        <h1>{{ $title }}</h1>

        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>

    @if (! $slot->isEmpty())
        <div class="page-heading-actions">
            {{ $slot }}
        </div>
    @endif
</div>
