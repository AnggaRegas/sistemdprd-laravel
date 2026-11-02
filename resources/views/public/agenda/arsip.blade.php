@extends('layouts.public')

@section('title', 'Arsip Agenda | DPRD Kota Parepare')
@section('meta_description', 'Arsip agenda dan kegiatan DPRD Kota Parepare yang telah berlangsung.')

@section('content')
    @php
        $statusStyles = [
            \App\Models\LandingAgenda::EVENT_UPCOMING => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\LandingAgenda::EVENT_ONGOING => 'border-[#e6d3a4] bg-[#f7efdc] text-[#8a6a24]',
            \App\Models\LandingAgenda::EVENT_COMPLETED => 'border-slate-200 bg-slate-100 text-slate-600',
            \App\Models\LandingAgenda::EVENT_CANCELLED => 'border-red-200 bg-red-50 text-red-700',
        ];
    @endphp

    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-16 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('agenda.index') }}" class="transition hover:text-[#e6c47d]">Agenda</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">Arsip</span>
            </nav>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        Riwayat kegiatan
                    </div>

                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[44px]">
                        Arsip Agenda
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                        Kumpulan agenda dan kegiatan DPRD Kota Parepare yang telah berlangsung, tersimpan sebagai dokumentasi publik.
                    </p>
                </div>

                <div class="lg:pb-1">
                    <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/[0.06] px-5 py-3 text-[13px] font-bold text-white transition hover:border-white/35 hover:bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M19 12H5m6 6-6-6 6-6"/>
                        </svg>
                        Kembali ke Agenda
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="container-public py-14 sm:py-16">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                    <span class="h-px w-8 bg-[#c9a45c]"></span>
                    Telah berlangsung
                </div>

                <h2 class="mt-3 text-2xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[32px]">
                    {{ $search !== '' ? 'Hasil Pencarian' : 'Agenda yang Telah Berlalu' }}
                </h2>

                <p class="mt-2 text-[12px] font-semibold text-[#7b8883]">
                    {{ $agendas->total() }} agenda{{ $search !== '' ? ' ditemukan' : ' terarsip' }}
                </p>
            </div>

            <form method="GET" action="{{ route('agenda.arsip') }}" class="w-full lg:max-w-sm" role="search">
                <label for="q" class="sr-only">Cari arsip agenda</label>
                <div class="flex items-center gap-2 rounded-2xl border border-[#e2dacb] bg-white p-1.5 shadow-[0_12px_30px_-24px_rgba(14,59,49,.5)] transition focus-within:border-[#0e3b31]/40">
                    <span class="pl-2.5 text-[#a8b1ad]">
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
                        placeholder="Cari arsip agenda..."
                        class="w-full min-w-0 border-0 bg-transparent px-1 py-2 text-[13px] text-[#16342c] placeholder:text-[#9aa49f] focus:ring-0"
                    >
                    <button type="submit" class="shrink-0 rounded-xl bg-[#0e3b31] px-4 py-2 text-[11px] font-extrabold text-white transition hover:bg-[#082a23]">
                        Cari
                    </button>
                </div>
            </form>
        </div>

        @if ($search !== '')
            <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] px-4 py-3 text-[12px] text-[#52605b]">
                <span>
                    Hasil pencarian untuk
                    <strong class="font-extrabold text-[#123b32]">&ldquo;{{ $search }}&rdquo;</strong>
                    &mdash; {{ $agendas->total() }} agenda ditemukan.
                </span>
                <a href="{{ route('agenda.arsip') }}" class="inline-flex items-center gap-1.5 rounded-full border border-[#ddd0b6] bg-white px-3 py-1.5 text-[11px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31]">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Hapus pencarian
                </a>
            </div>
        @endif

        @if ($agendas->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-[#ddd0b6] bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f1ecdf] text-[#a17f3e]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M3 7h18v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7Z"/>
                        <path d="M8 7V4h8v3M10 12h4"/>
                    </svg>
                </span>

                <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">
                    {{ $search !== '' ? 'Arsip tidak ditemukan' : 'Arsip agenda masih kosong' }}
                </h3>

                <p class="mx-auto mt-2 max-w-md text-[13px] leading-7 text-[#6b7873]">
                    @if ($search !== '')
                        Tidak ada agenda arsip yang cocok dengan kata kunci tersebut. Coba gunakan kata kunci lain.
                    @else
                        Agenda yang telah berlangsung akan tercatat di arsip ini secara otomatis.
                    @endif
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($search !== '')
                        <a href="{{ route('agenda.arsip') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-white transition hover:bg-[#082a23]">
                            Lihat Semua Arsip
                        </a>
                    @endif

                    <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] bg-white px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                        Kembali ke Agenda
                    </a>
                </div>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($agendas as $agenda)
                    @php
                        $startTime = $agenda->start_time ? substr((string) $agenda->start_time, 0, 5) : null;
                        $endTime = $agenda->end_time ? substr((string) $agenda->end_time, 0, 5) : null;
                        $badge = $statusStyles[$agenda->event_status] ?? 'border-slate-200 bg-slate-100 text-slate-600';
                    @endphp

                    <article class="group flex flex-col gap-5 rounded-3xl border border-[#e7dfd0] bg-white p-5 transition duration-300 hover:-translate-y-0.5 hover:border-[#d3c29a] hover:shadow-[0_24px_55px_-40px_rgba(14,59,49,.5)] sm:flex-row sm:items-center sm:p-6">
                        <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-2xl border border-[#e7dfd0] bg-[#f7f4ec] text-[#123b32]">
                            <span class="text-[10px] font-extrabold uppercase tracking-[.16em] text-[#a17f3e]">
                                {{ $agenda->event_date?->translatedFormat('M') ?? '—' }}
                            </span>
                            <span class="mt-0.5 text-2xl font-extrabold leading-none">
                                {{ $agenda->event_date?->format('d') ?? '—' }}
                            </span>
                            <span class="mt-1 text-[9px] font-semibold tracking-wide text-[#8a948f]">
                                {{ $agenda->event_date?->format('Y') ?? '' }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[.12em] {{ $badge }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $agenda->event_status_label ?: 'Selesai' }}
                                </span>
                            </div>

                            <h3 class="mt-2.5 text-[17px] font-extrabold leading-7 tracking-[-0.01em] text-[#123b32] sm:text-lg">
                                <a href="{{ route('agenda.show', $agenda->slug) }}" class="transition hover:text-[#a17f3e]">
                                    {{ $agenda->title }}
                                </a>
                            </h3>

                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] font-semibold text-[#8a948f]">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                                        <path d="M8 3v4M16 3v4M3 10h18"/>
                                    </svg>
                                    <time datetime="{{ $agenda->event_date?->toDateString() }}">
                                        {{ $agenda->event_date?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}
                                    </time>
                                </span>

                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 7v5l3.5 2"/>
                                    </svg>
                                    @if ($startTime)
                                        <span>{{ $startTime }}@if ($endTime) &ndash; {{ $endTime }}@endif</span>
                                    @else
                                        <span>Waktu tidak dicatat</span>
                                    @endif
                                </span>

                                <span class="inline-flex min-w-0 items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-[#c9a45c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>
                                    <span class="truncate">{{ $agenda->location ?: 'Lokasi belum dicatat' }}</span>
                                </span>
                            </div>

                            @if ($agenda->description)
                                <p class="mt-2.5 text-[12px] leading-6 text-[#6b7873]">
                                    {{ \Illuminate\Support\Str::limit($agenda->description, 150) }}
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 sm:pl-3">
                            <a href="{{ route('agenda.show', $agenda->slug) }}" class="inline-flex items-center gap-2 rounded-xl border border-[#e2dacb] px-4 py-2.5 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-2.5 hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-white">
                                Lihat Detail
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($agendas->hasPages())
                <div class="mt-10">
                    {{ $agendas->links() }}
                </div>
            @endif
        @endif

        <div class="mt-12 flex flex-col gap-3 rounded-3xl border border-[#e7dfd0] bg-[#fbf9f4] px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] bg-white px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 12H5m6 6-6-6 6-6"/>
                </svg>
                Kembali ke Agenda
            </a>

            <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                Beranda DPRD Kota Parepare
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                </svg>
            </a>
        </div>
    </section>
@endsection
