@extends('layouts.public')

@section('title', e($gallery->title).' | Galeri Dokumentasi DPRD Kota Parepare')
@section('meta_description', e(\Illuminate\Support\Str::limit((string) $gallery->description, 150)))

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-28 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('dokumentasi.index') }}" class="transition hover:text-[#e6c47d]">Dokumentasi</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">{{ \Illuminate\Support\Str::limit($gallery->title, 48) }}</span>
            </nav>

            <div class="mt-8 max-w-4xl">
                <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                    Detail album
                </div>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[42px] sm:leading-[1.15]">
                    {{ $gallery->title }}
                </h1>

                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2.5 text-[12px] font-semibold text-white/55 sm:text-[13px]">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path d="M8 3v4M16 3v4M3 10h18"/>
                        </svg>
                        <time datetime="{{ $gallery->activity_date?->toDateString() }}">
                            {{ $gallery->activity_date?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                        </time>
                    </span>

                    @if ($gallery->location)
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                <circle cx="12" cy="10" r="2.5"/>
                            </svg>
                            {{ $gallery->location }}
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/>
                            <circle cx="12" cy="13" r="3.2"/>
                        </svg>
                        {{ $gallery->photos->count() }} foto dokumentasi
                    </span>
                </div>

                <p class="mt-5 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                    {{ \Illuminate\Support\Str::limit((string) $gallery->description, 320) }}
                </p>
            </div>
        </div>
    </section>

    <section class="relative z-10 container-public -mt-14 pb-20">
        <div class="overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white p-2.5 shadow-[0_28px_80px_-45px_rgba(14,59,49,.5)] sm:p-3">
            @if ($gallery->cover_image)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::url($gallery->cover_image) }}"
                    alt="{{ $gallery->title }}"
                    class="h-[220px] w-full rounded-2xl object-cover object-center sm:h-[420px]"
                >
            @else
                <div class="relative flex h-[200px] items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-[#0e3b31] via-[#124437] to-[#082a23] text-[#e6c47d]/30 sm:h-[300px]">
                    <div class="hero-grid absolute inset-0 opacity-70"></div>
                    <svg class="relative h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="9" cy="10" r="1.6"/>
                        <path d="m4 18 5-5 4 4 3-3 4 4"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="mt-8 rounded-3xl border border-[#e7dfd0] bg-white p-6 shadow-[0_20px_55px_-40px_rgba(14,59,49,.45)] sm:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                        <span class="h-px w-8 bg-[#c9a45c]"></span>
                        Isi album
                    </div>

                    <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32]">
                        Galeri Foto
                    </h2>
                </div>

                @if ($gallery->photos->isNotEmpty())
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-[#e2dacb] bg-[#f7f4ec] px-3.5 py-1.5 text-[11px] font-extrabold text-[#a17f3e]">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="9" cy="10" r="1.6"/>
                            <path d="m4 18 5-5 4 4 3-3 4 4"/>
                        </svg>
                        {{ $gallery->photos->count() }} foto
                    </span>
                @endif
            </div>

            @if ($gallery->photos->isEmpty())
                <p class="mt-8 rounded-2xl border border-dashed border-[#ddd0b6] bg-[#fbf9f4] px-5 py-10 text-center text-[13px] leading-7 text-[#6b7873]">
                    Belum ada foto yang diunggah pada album ini. Silakan kunjungi halaman lain di portal untuk informasi terbaru.
                </p>
            @else
                <div class="mt-8 cards-grid" style="--cards-gap: 1rem">
                    @foreach ($gallery->photos as $photo)
                        @php
                            $photoCaption = trim((string) $photo->caption);
                        @endphp

                        <figure class="group overflow-hidden rounded-2xl border border-[#eee8dc] bg-[#fbf9f4]">
                            <button
                                type="button"
                                class="relative block w-full cursor-zoom-in overflow-hidden bg-[#0e3b31]"
                                data-lightbox-src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}"
                                data-lightbox-caption="{{ $photoCaption }}"
                                aria-label="Perbesar foto dari album {{ $gallery->title }}"
                            >
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::url($photo->image_path) }}"
                                    alt="{{ $photoCaption !== '' ? $photoCaption : $gallery->title }}"
                                    loading="lazy"
                                    class="aspect-[4/3] w-full object-cover object-center transition duration-500 group-hover:scale-[1.03]"
                                >

                                <span class="pointer-events-none absolute inset-0 bg-[#082a23]/0 transition duration-300 group-hover:bg-[#082a23]/20" aria-hidden="true"></span>

                                <span class="absolute left-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg bg-[#082a23]/70 text-[#e6c47d] opacity-0 backdrop-blur transition duration-300 group-hover:opacity-100" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M15 3h6v6M21 3l-7 7M9 21H3v-6M3 21l7-7"/>
                                    </svg>
                                </span>
                            </button>

                            @if ($photoCaption !== '')
                                <figcaption class="border-t border-[#eee8dc] px-4 py-3 text-[11px] leading-5 text-[#63716c]">
                                    {{ $photoCaption }}
                                </figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>

                <p class="mt-5 text-center text-[11px] font-semibold text-[#8a948f]">
                    Klik foto untuk memperbesar. Gunakan tombol panah kiri/kanan untuk berpindah foto.
                </p>
            @endif
        </div>

        @if ($otherGalleries->isNotEmpty())
            <div class="mt-14">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                            <span class="h-px w-8 bg-[#c9a45c]"></span>
                            Album lainnya
                        </div>

                        <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32]">
                            Album Lainnya
                        </h2>
                    </div>

                    <a href="{{ route('dokumentasi.index') }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Lihat semua album
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>

                <div class="mt-7 cards-grid">
                    @foreach ($otherGalleries as $otherGallery)
                        <article class="group flex flex-col overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)]">
                            <a href="{{ route('dokumentasi.show', $otherGallery->slug) }}" class="relative block aspect-[16/10] overflow-hidden bg-[#0e3b31]">
                                @if ($otherGallery->cover_image)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($otherGallery->cover_image) }}"
                                        alt="{{ $otherGallery->title }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.04]"
                                    >
                                @else
                                    <div class="absolute inset-0 bg-gradient-to-br from-[#0e3b31] via-[#124437] to-[#082a23]">
                                        <div class="hero-grid absolute inset-0 opacity-70"></div>
                                        <div class="absolute inset-0 flex items-center justify-center text-[#e6c47d]/30">
                                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                                <circle cx="9" cy="10" r="1.6"/>
                                                <path d="m4 18 5-5 4 4 3-3 4 4"/>
                                            </svg>
                                        </div>
                                    </div>
                                @endif

                                <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-[#082a23]/80 px-3 py-1 text-[10px] font-extrabold text-[#f0dfb8] backdrop-blur">
                                    {{ $otherGallery->photos_count ?? 0 }} Foto
                                </span>
                            </a>

                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-semibold text-[#8a948f]">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                                            <path d="M8 3v4M16 3v4M3 10h18"/>
                                        </svg>
                                        <time datetime="{{ $otherGallery->activity_date?->toDateString() }}">
                                            {{ $otherGallery->activity_date?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                                        </time>
                                    </span>

                                    @if ($otherGallery->location)
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                                <circle cx="12" cy="10" r="2.5"/>
                                            </svg>
                                            <span class="truncate">{{ $otherGallery->location }}</span>
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-3 text-[15px] font-extrabold leading-6 text-[#123b32]">
                                    <a href="{{ route('dokumentasi.show', $otherGallery->slug) }}" class="transition hover:text-[#a17f3e]">
                                        {{ $otherGallery->title }}
                                    </a>
                                </h3>

                                <div class="mt-auto pt-4">
                                    <a href="{{ route('dokumentasi.show', $otherGallery->slug) }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                                        Lihat Album
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-12 flex flex-col gap-3 rounded-3xl border border-[#e7dfd0] bg-[#fbf9f4] px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <a href="{{ route('dokumentasi.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] bg-white px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 12H5m6 6-6-6 6-6"/>
                </svg>
                Kembali ke Dokumentasi
            </a>

            <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                Lihat Agenda Kegiatan
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                </svg>
            </a>
        </div>

        <div
            id="photo-lightbox"
            data-lightbox
            class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#04160f]/95 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-label="Pratinjau foto dokumentasi"
        >
            <button
                type="button"
                data-lightbox-close
                class="absolute right-4 top-4 z-20 flex h-11 w-11 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-white transition hover:bg-white/20"
                aria-label="Tutup pratinjau"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>

            <button
                type="button"
                data-lightbox-prev
                class="absolute left-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/20 sm:left-6"
                aria-label="Foto sebelumnya"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M15 6l-6 6 6 6"/>
                </svg>
            </button>

            <button
                type="button"
                data-lightbox-next
                class="absolute right-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/20 sm:right-6"
                aria-label="Foto berikutnya"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="m9 6 6 6-6 6"/>
                </svg>
            </button>

            <figure class="relative z-10 flex max-h-full flex-col items-center">
                <img
                    data-lightbox-image
                    src=""
                    alt=""
                    class="max-h-[76vh] max-w-[92vw] rounded-2xl object-contain shadow-2xl sm:max-w-[82vw]"
                >

                <figcaption data-lightbox-caption class="mt-4 max-w-xl text-center text-[12px] leading-6 text-white/70"></figcaption>

                <p data-lightbox-counter class="mt-1.5 text-[10px] font-bold uppercase tracking-[.2em] text-[#e6c47d]/80"></p>
            </figure>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var overlay = document.querySelector('[data-lightbox]');
            var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox-src]'));

            if (!overlay || triggers.length === 0) {
                return;
            }

            var image = overlay.querySelector('[data-lightbox-image]');
            var caption = overlay.querySelector('[data-lightbox-caption]');
            var counter = overlay.querySelector('[data-lightbox-counter]');
            var closeButton = overlay.querySelector('[data-lightbox-close]');
            var prevButton = overlay.querySelector('[data-lightbox-prev]');
            var nextButton = overlay.querySelector('[data-lightbox-next]');
            var currentIndex = 0;
            var lastFocused = null;

            function show(index) {
                var trigger = triggers[(index + triggers.length) % triggers.length];

                if (!trigger || !image) {
                    return;
                }

                currentIndex = (index + triggers.length) % triggers.length;
                var triggerCaption = trigger.getAttribute('data-lightbox-caption') || '';

                image.src = trigger.getAttribute('data-lightbox-src') || '';
                image.alt = triggerCaption;
                caption.textContent = triggerCaption;
                counter.textContent = (currentIndex + 1) + ' / ' + triggers.length;
            }

            function openLightbox(index) {
                show(index);
                lastFocused = document.activeElement;
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                closeButton.focus();
            }

            function closeLightbox() {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';

                if (image) {
                    image.src = '';
                }

                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus();
                }
            }

            triggers.forEach(function (trigger, index) {
                trigger.addEventListener('click', function () {
                    openLightbox(index);
                });
            });

            if (closeButton) {
                closeButton.addEventListener('click', closeLightbox);
            }

            if (prevButton) {
                prevButton.addEventListener('click', function () {
                    show(currentIndex - 1);
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    show(currentIndex + 1);
                });
            }

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (overlay.classList.contains('hidden')) {
                    return;
                }

                if (event.key === 'Escape') {
                    closeLightbox();
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    show(currentIndex - 1);
                } else if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    show(currentIndex + 1);
                }
            });
        });
    </script>
@endpush
