@props([
    'label',
    'value',
    'icon',
    'tint' => '#eef6f1',
    'color' => '#155043',
    'trend' => null,
    'trendType' => 'flat',
    'meta' => null,
])

<div {{ $attributes->merge(['class' => 'app-card stat-card']) }} style="--stat-tint: {{ $tint }}; --stat-color: {{ $color }};">
    <div class="stat-card-top">
        <div>
            <p class="stat-label">{{ $label }}</p>
            <p class="stat-value">{{ number_format($value, 0, ',', '.') }}</p>
        </div>
        <span class="stat-icon" aria-hidden="true">
            <i class="{{ $icon }}"></i>
        </span>
    </div>

    <div class="stat-meta">
        @if ($trend)
            <span class="stat-trend trend-{{ $trendType }}">
                @if ($trendType === 'up')
                    <i class="ri-arrow-up-line" aria-hidden="true"></i>
                @elseif ($trendType === 'warn')
                    <i class="ri-alert-line" aria-hidden="true"></i>
                @elseif ($trendType === 'ok')
                    <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                @else
                    <i class="ri-subtract-line" aria-hidden="true"></i>
                @endif
                {{ $trend }}
            </span>
        @endif

        @if ($meta)
            <span>{{ $meta }}</span>
        @endif
    </div>
</div>
