@props([
    'icon' => 'ri-inbox-archive-line',
    'title' => 'Belum ada data',
    'description' => null,
])

<div class="empty-state">
    <div class="empty-state-icon" aria-hidden="true">
        <i class="{{ $icon }}"></i>
    </div>
    <h3>{{ $title }}</h3>

    @if ($description)
        <p>{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
