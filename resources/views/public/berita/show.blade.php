@extends('layouts.public')

@section('title', e($article->title).' | Berita DPRD Kota Parepare')
@section('meta_description', e(\Illuminate\Support\Str::limit($article->excerpt ?: strip_tags((string) $article->content), 150)))

@section('content')
    @php
        $categoryLabel = \App\Models\LandingNews::CATEGORIES[$article->category] ?? ucfirst((string) $article->category);
        $categoryLabel = $categoryLabel !== '' ? $categoryLabel : 'Berita';
    @endphp

    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-16 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('berita.index') }}" class="transition hover:text-[#e6c47d]">Berita &amp; Kegiatan</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">{{ \Illuminate\Support\Str::limit($article->title, 48) }}</span>
            </nav>

            <div class="mt-8 max-w-4xl">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] px-3.5 py-1.5 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#f0dfb8] backdrop-blur">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        {{ $categoryLabel }}
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3.5 py-1.5 text-[10px] font-semibold text-white/55">
                        <svg class="h-3.5 w-3.5 text-[#e6c47d]/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path d="M8 3v4M16 3v4M3 10h18"/>
                        </svg>
                        <time datetime="{{ $article->published_at?->toDateString() }}">
                            {{ $article->published_at?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                        </time>
                    </span>
                </div>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[42px] sm:leading-[1.15]">
                    {{ $article->title }}
                </h1>
            </div>
        </div>
    </section>

    <section class="container-public pb-20 pt-10 sm:pt-12">
        <article class="mx-auto max-w-4xl overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white shadow-[0_28px_80px_-45px_rgba(14,59,49,.5)]">
            @if ($article->thumbnail)
                <div class="border-b border-[#f0ebe0] p-2 sm:p-3">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::url($article->thumbnail) }}"
                        alt="{{ $article->title }}"
                        class="aspect-[16/9] w-full rounded-2xl object-cover"
                    >
                </div>
            @endif

            <div class="p-6 sm:p-9">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-[#f0ebe0] pb-5 text-[11px] font-semibold text-[#8a948f]">
                    <span class="inline-flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#f1ecdf] text-[#a17f3e]">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-4h6v4"/>
                            </svg>
                        </span>
                        DPRD Kota Parepare
                    </span>

                    <span class="h-1 w-1 rounded-full bg-[#d8cbb1]" aria-hidden="true"></span>

                    <span>Kategori {{ $categoryLabel }}</span>
                </div>

                @if ($article->excerpt)
                    <p class="mt-6 text-[15px] font-semibold leading-8 text-[#33443e] sm:text-base">
                        {{ $article->excerpt }}
                    </p>
                @endif

                <div class="mt-6 text-[15px] leading-8 text-[#4c5a55]">
                    @if (trim((string) $article->content) !== '')
                        {!! nl2br(e($article->content)) !!}
                    @else
                        <p class="text-[13px] italic text-[#8a948f]">Isi berita belum tersedia.</p>
                    @endif
                </div>

                <div class="mt-9 flex flex-col gap-3 border-t border-[#f0ebe0] pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('berita.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M19 12H5m6 6-6-6 6-6"/>
                        </svg>
                        Kembali ke Berita
                    </a>

                    <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 text-[12px] font-bold text-[#8a948f] transition hover:text-[#0e3b31]">
                        Beranda DPRD Kota Parepare
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </article>

        @if ($related->isNotEmpty())
            <div class="mt-14">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                            <span class="h-px w-8 bg-[#c9a45c]"></span>
                            Berita lainnya
                        </div>

                        <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
                            Berita Lainnya
                        </h2>
                    </div>

                    <a href="{{ route('berita.index') }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Lihat semua berita
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>

                <div class="mt-7 cards-grid">
                    @foreach ($related as $item)
                        @php
                            $itemCategory = \App\Models\LandingNews::CATEGORIES[$item->category] ?? ucfirst((string) $item->category);
                            $itemExcerpt = $item->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $item->content), 110);
                        @endphp

                        <article class="group flex flex-col overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)]">
                            <a href="{{ route('berita.show', $item->slug) }}" class="relative block aspect-[16/9] overflow-hidden bg-[#0e3b31]">
                                @if ($item->thumbnail)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($item->thumbnail) }}"
                                        alt="{{ $item->title }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                    >
                                @else
                                    <div class="absolute inset-0 bg-gradient-to-br from-[#0e3b31] via-[#124437] to-[#082a23]">
                                        <div class="hero-grid absolute inset-0 opacity-70"></div>
                                        <div class="absolute inset-0 flex items-center justify-center text-[#e6c47d]/35">
                                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                                <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                                                <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                                                <path d="M7.5 9h8M7.5 13h8M7.5 17h5"/>
                                            </svg>
                                        </div>
                                    </div>
                                @endif

                                <span class="absolute left-4 top-4 rounded-full border border-white/20 bg-[#082a23]/75 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[.14em] text-[#f0dfb8] backdrop-blur">
                                    {{ $itemCategory !== '' ? $itemCategory : 'Berita' }}
                                </span>
                            </a>

                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-center gap-2 text-[11px] font-semibold text-[#8a948f]">
                                    <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                                        <path d="M8 3v4M16 3v4M3 10h18"/>
                                    </svg>
                                    <time datetime="{{ $item->published_at?->toDateString() }}">
                                        {{ $item->published_at?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                                    </time>
                                </div>

                                <h3 class="mt-3 text-[15px] font-extrabold leading-6 text-[#123b32]">
                                    <a href="{{ route('berita.show', $item->slug) }}" class="transition hover:text-[#a17f3e]">
                                        {{ $item->title }}
                                    </a>
                                </h3>

                                <p class="mt-2 text-[12px] leading-6 text-[#6b7873]">
                                    {{ $itemExcerpt }}
                                </p>

                                <div class="mt-auto pt-4">
                                    <a href="{{ route('berita.show', $item->slug) }}" class="inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
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
            </div>
        @endif
    </section>
@endsection
