@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $start = max(1, $paginator->currentPage() - 2);
        $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
    @endphp

    <nav aria-label="Navigasi halaman">
        <ul class="pagination-modern">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true"><i class="ri-arrow-left-s-line"></i></span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                        <i class="ri-arrow-left-s-line" aria-hidden="true"></i>
                    </a>
                @endif
            </li>

            @foreach ($paginator->getUrlRange($start, $end) as $page => $url)
                <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                    @if ($page === $paginator->currentPage())
                        <span class="page-link" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                        <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="page-link" aria-hidden="true"><i class="ri-arrow-right-s-line"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
