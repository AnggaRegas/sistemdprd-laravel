@props([
    'steps' => [],
    'current' => 1,
])

<nav class="archive-stepper" aria-label="Tahapan arsip">
    <ol class="archive-stepper-list">
        @foreach ($steps as $index => $step)
            @php
                $number = $index + 1;
                $isComplete = $number < $current;
                $isActive = $number === $current;
                $hasLink = $isComplete && ! empty($step['url']);
            @endphp

            <li class="archive-step {{ $isComplete ? 'is-complete' : '' }} {{ $isActive ? 'is-active' : '' }}">
                @if ($hasLink)
                    <a href="{{ $step['url'] }}" class="archive-step-link" title="Kembali ke {{ $step['label'] }}">
                @else
                    <span class="archive-step-link">
                @endif

                    <span class="archive-step-index">
                        @if ($isComplete)
                            <i class="ri-check-line" aria-hidden="true"></i>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span class="archive-step-label">{{ $step['label'] }}</span>

                @if ($hasLink)
                    </a>
                @else
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
