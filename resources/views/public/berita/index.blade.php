@extends('layouts.public')

@section('title', 'Berita & Kegiatan | DPRD Kota Parepare')
@section('meta_description', 'Kumpulan berita, kegiatan, pengumuman, dan publikasi resmi DPRD Kota Parepare.')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-16 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">Berita &amp; Kegiatan</span>
            </nav>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.55fr)] lg:items-end">
                <div>
                    <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        Publikasi resmi
                    </div>

                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[44px]">
                        Berita &amp; Kegiatan
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                        Ikuti kabar terbaru seputar kegiatan, pengumuman, dan publikasi resmi DPRD Kota Parepare.
                    </p>
                </div>

                <form method="GET" action="{{ route('berita.index') }}" class="w-full" role="search">
                    <label for="q" class="sr-only">Cari berita</label>
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
                            placeholder="Cari judul berita..."
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
                    Kabar terbaru
                </div>

                <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[32px]">
                    {{ $search !== '' ? 'Hasil Pencarian' : 'Berita Terbaru' }}
                </h2>
            </div>

            <p class="text-[12px] font-semibold text-[#7b8883]">
                {{ $news->total() }} berita{{ $search !== '' ? ' ditemukan' : ' dipublikasikan' }}
            </p>
        </div>

        @if ($search !== '')
            <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] px-4 py-3 text-[12px] text-[#52605b]">
                <span>
                    Hasil pencarian untuk
                    <strong class="font-extrabold text-[#123b32]">&ldquo;{{ $search }}&rdquo;</strong>
                    &mdash; {{ $news->total() }} berita ditemukan.
                </span>
                <a href="{{ route('berita.index') }}" class="inline-flex items-center gap-1.5 rounded-full border border-[#ddd0b6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31]">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Hapus pencarian
                </a>
            </div>
        @endif

        @if ($news->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-[#ddd0b6] bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f1ecdf] text-[#a17f3e]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                        <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                        <path d="M7.5 9h8M7.5 13h8M7.5 17h5"/>
                    </svg>
                </span>

                <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">
                    {{ $search !== '' ? 'Berita tidak ditemukan' : 'Belum ada berita' }}
                </h3>

                <p class="mx-auto mt-2 max-w-md text-[13px] leading-7 text-[#6b7873]">
                    @if ($search !== '')
                        Tidak ada berita yang cocok dengan kata kunci tersebut. Coba gunakan kata kunci lain.
                    @else
                        Berita dan kegiatan terbaru akan tampil di halaman ini setelah dipublikasikan.
                    @endif
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($search !== '')
                        <a href="{{ route('berita.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-white transition hover:bg-[#082a23]">
                            Lihat Semua Berita
                        </a>
                    @endif

                    <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 cards-grid">
                @foreach ($news as $article)
                    @php
                        $categoryLabel = \App\Models\LandingNews::CATEGORIES[$article->category] ?? ucfirst((string) $article->category);
                        $excerpt = $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->content), 130);
                    @endphp

                    <article class="group flex flex-col overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)]">
                        <a href="{{ route('berita.show', $article->slug) }}" class="relative block aspect-[16/10] overflow-hidden bg-[#0e3b31]">
                            @if ($article->thumbnail)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::url($article->thumbnail) }}"
                                    alt="{{ $article->title }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                >
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-[#0e3b31] via-[#124437] to-[#082a23]">
                                    <div class="hero-grid absolute inset-0 opacity-70"></div>
                                    <div class="absolute inset-0 flex items-center justify-center text-[#e6c47d]/35">
                                        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                            <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                                            <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                                            <path d="M7.5 9h8M7.5 13h8M7.5 17h5"/>
                                        </svg>
                                    </div>
                                </div>
                            @endif

                            <span class="absolute left-4 top-4 rounded-full border border-white/20 bg-[#082a23]/75 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.14em] text-[#f0dfb8] backdrop-blur">
                                {{ $categoryLabel !== '' ? $categoryLabel : 'Berita' }}
                            </span>
                        </a>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex items-center gap-2 text-[11px] font-semibold text-[#8a948f]">
                                <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M8 3v4M16 3v4M3 10h18"/>
                                </svg>
                                <time datetime="{{ $article->published_at?->toDateString() }}">
                                    {{ $article->published_at?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                                </time>
                            </div>

                            <h3 class="mt-3 text-[17px] font-extrabold leading-7 tracking-[-0.01em] text-[#123b32]">
                                <a href="{{ route('berita.show', $article->slug) }}" class="transition hover:text-[#a17f3e]">
                                    {{ $article->title }}
                                </a>
                            </h3>

                            <p class="mt-3 text-[12px] leading-6 text-[#6b7873]">
                                {{ $excerpt }}
                            </p>

                            <div class="mt-auto pt-5">
                                <a href="{{ route('berita.show', $article->slug) }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                                    Baca Selengkapnya
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($news->hasPages())
                <div class="mt-10">
                    {{ $news->links() }}
                </div>
            @endif
        @endif

        <section class="relative mt-14 overflow-hidden rounded-3xl bg-[#0e3b31] px-6 py-10 text-white sm:px-9 sm:py-12">
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-12 h-64 w-64 rounded-full bg-emerald-300/10 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.22em] text-[#e6c47d]">Partisipasi masyarakat</p>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] sm:text-[30px]">
                        Punya aspirasi atau informasi untuk kami?
                    </h2>
                    <p class="mt-2 text-[13px] leading-7 text-white/60">
                        Sampaikan aspirasi, masukan, atau laporan Anda melalui portal resmi DPRD Kota Parepare.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('aspirasi.create') }}" class="group inline-flex items-center justify-center gap-2.5 rounded-xl bg-[#e6c47d] px-5 py-3 text-[13px] font-extrabold text-[#123b32] transition hover:-translate-y-0.5 hover:bg-[#f0d696]">
                        Sampaikan Aspirasi
                        <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>

                    <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/[0.04] px-5 py-3 text-[13px] font-bold text-white transition hover:border-white/35 hover:bg-white/10">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </section>
    </section>
@endsection
