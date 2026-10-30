@props(['items' => []])

<div class="archive-context-strip">
    @foreach ($items as $item)
        <div class="archive-context-item">
            <span class="archive-context-icon" aria-hidden="true">
                <i class="{{ $item['icon'] ?? 'ri-folder-line' }}"></i>
            </span>
            <div>
                <span>{{ $item['label'] }}</span>
                <strong>{{ $item['value'] }}</strong>
            </div>
        </div>
    @endforeach
</div>
