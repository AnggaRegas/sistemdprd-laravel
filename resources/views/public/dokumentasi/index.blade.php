@extends('layouts.public')

@section('title', 'Galeri Dokumentasi | DPRD Kota Parepare')
@section('meta_description', 'Dokumentasi foto kegiatan dan aktivitas resmi DPRD Kota Parepare.')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-16 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">Dokumentasi</span>
            </nav>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.55fr)] lg:items-end">
                <div>
                    <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        Arsip visual kegiatan
                    </div>

                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[44px]">
                        Galeri Dokumentasi
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                        Kumpulan foto kegiatan, rapat, dan aktivitas resmi DPRD Kota Parepare yang terdokumentasi.
                    </p>
                </div>

                <form method="GET" action="{{ route('dokumentasi.index') }}" class="w-full" role="search">
                    <label for="q" class="sr-only">Cari album dokumentasi</label>
                    <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-white/[0.07] p-2 backdrop-blur transition focus-within:border-[#e6c47d]/60">
                        <span class="pl-2 text-white/40">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="m20 20-3.5-3.5"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ $search }}"
                            maxlength="100"
                            placeholder="Cari album dokumentasi..."
                            class="w-full min-w-0 border-0 bg-transparent px-1 py-2 text-sm text-white placeholder:text-white/40 focus:ring-0"
                        >
                        <button type="submit" class="shrink-0 rounded-xl bg-[#e6c47d] px-4 py-2 text-[12px] font-extrabold text-[#123b32] transition hover:bg-[#f0d696]">
                            Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="container-public py-14 sm:py-16">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                    <span class="h-px w-8 bg-[#c9a45c]"></span>
                    Album kegiatan
                </div>

                <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[32px]">
                    {{ $search !== '' ? 'Hasil Pencarian' : 'Dokumentasi Terbaru' }}
                </h2>
            </div>

            <p class="text-[12px] font-semibold text-[#7b8883]">
                {{ $galleries->total() }} album{{ $search !== '' ? ' ditemukan' : ' dokumentasi' }}
            </p>
        </div>

        @if ($search !== '')
            <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] px-4 py-3 text-[12px] text-[#52605b]">
                <span>
                    Hasil pencarian untuk
                    <strong class="font-extrabold text-[#123b32]">&ldquo;{{ $search }}&rdquo;</strong>
                    &mdash; {{ $galleries->total() }} album ditemukan.
                </span>
                <a href="{{ route('dokumentasi.index') }}" class="inline-flex items-center gap-1.5 rounded-full border border-[#ddd0b6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31]">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Hapus pencarian
                </a>
            </div>
        @endif

        @if ($galleries->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-[#ddd0b6] bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f1ecdf] text-[#a17f3e]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <circle cx="9" cy="10" r="1.6"/>
                        <path d="m4 18 5-5 4 4 3-3 4 4"/>
                    </svg>
                </span>

                <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">
                    {{ $search !== '' ? 'Album tidak ditemukan' : 'Belum ada album dokumentasi' }}
                </h3>

                <p class="mx-auto mt-2 max-w-md text-[13px] leading-7 text-[#6b7873]">
                    @if ($search !== '')
                        Tidak ada album yang cocok dengan kata kunci tersebut. Coba gunakan kata kunci lain.
                    @else
                        Dokumentasi foto kegiatan akan tampil di halaman ini setelah dipublikasikan.
                    @endif
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($search !== '')
                        <a href="{{ route('dokumentasi.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-white transition hover:bg-[#082a23]">
                            Lihat Semua Album
                        </a>
                    @endif

                    <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 cards-grid">
                @foreach ($galleries as $gallery)
                    <article class="group flex flex-col overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)]">
                        <a href="{{ route('dokumentasi.show', $gallery->slug) }}" class="relative block aspect-[4/3] overflow-hidden bg-[#0e3b31]">
                            @if ($gallery->cover_image)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::url($gallery->cover_image) }}"
                                    alt="{{ $gallery->title }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.04]"
                                >
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-[#0e3b31] via-[#124437] to-[#082a23]">
                                    <div class="hero-grid absolute inset-0 opacity-70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center text-[#e6c47d]/30">
                                        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <circle cx="9" cy="10" r="1.6"/>
                                            <path d="m4 18 5-5 4 4 3-3 4 4"/>
                                        </svg>
                                    </div>
                                </div>
                            @endif

                            <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-[#082a23]/80 px-3 py-1 text-[10px] font-extrabold text-[#f0dfb8] backdrop-blur">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/>
                                    <circle cx="12" cy="13" r="3.2"/>
                                </svg>
                                {{ $gallery->photos_count ?? 0 }} Foto
                            </span>

                            <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#082a23]/45 via-transparent to-transparent opacity-0 transition duration-300 group-hover:opacity-100"></span>
                        </a>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-semibold text-[#8a948f]">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                                        <path d="M8 3v4M16 3v4M3 10h18"/>
                                    </svg>
                                    <time datetime="{{ $gallery->activity_date?->toDateString() }}">
                                        {{ $gallery->activity_date?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                                    </time>
                                </span>

                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>
                                    <span class="truncate">{{ $gallery->location ?: 'Lokasi belum dicatat' }}</span>
                                </span>
                            </div>

                            <h3 class="mt-3 text-[17px] font-extrabold leading-7 tracking-[-0.01em] text-[#123b32]">
                                <a href="{{ route('dokumentasi.show', $gallery->slug) }}" class="transition hover:text-[#a17f3e]">
                                    {{ $gallery->title }}
                                </a>
                            </h3>

                            @if ($gallery->description)
                                <p class="mt-2 text-[12px] leading-6 text-[#6b7873]">
                                    {{ \Illuminate\Support\Str::limit($gallery->description, 110) }}
                                </p>
                            @endif

                            <div class="mt-auto pt-5">
                                <a href="{{ route('dokumentasi.show', $gallery->slug) }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
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

            @if ($galleries->hasPages())
                <div class="mt-10">
                    {{ $galleries->links() }}
                </div>
            @endif
        @endif

        <div class="mt-14 flex flex-col gap-4 rounded-3xl border border-[#e7dfd0] bg-[#fbf9f4] px-6 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">Informasi terkait</p>
                <p class="mt-1.5 text-[14px] font-extrabold text-[#123b32]">Ingin tahu jadwal kegiatan berikutnya?</p>
                <p class="mt-1 text-[12px] leading-6 text-[#6b7873]">Lihat agenda kegiatan DPRD Kota Parepare yang akan datang.</p>
            </div>

            <div class="flex flex-col gap-2.5 sm:flex-row">
                <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-4 py-2.5 text-[12px] font-extrabold text-white transition hover:bg-[#082a23]">
                    Lihat Agenda
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                    </svg>
                </a>

                <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] bg-white px-4 py-2.5 text-[12px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                    Beranda
                </a>
            </div>
        </div>
    </section>
@endsection
