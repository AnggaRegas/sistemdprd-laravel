@extends('layouts.public')

@section('title', e($agenda->title).' | Agenda DPRD Kota Parepare')
@section('meta_description', e(\Illuminate\Support\Str::limit((string) $agenda->description, 150)))

@section('content')
    @php
        $statusStyles = [
            \App\Models\LandingAgenda::EVENT_UPCOMING => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\LandingAgenda::EVENT_ONGOING => 'border-[#e6d3a4] bg-[#f7efdc] text-[#8a6a24]',
            \App\Models\LandingAgenda::EVENT_COMPLETED => 'border-slate-200 bg-slate-100 text-slate-600',
            \App\Models\LandingAgenda::EVENT_CANCELLED => 'border-red-200 bg-red-50 text-red-700',
        ];
        $badge = $statusStyles[$agenda->event_status] ?? 'border-slate-200 bg-slate-100 text-slate-600';
        $startTime = $agenda->start_time ? substr((string) $agenda->start_time, 0, 5) : null;
        $endTime = $agenda->end_time ? substr((string) $agenda->end_time, 0, 5) : null;
    @endphp

    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative container-public pb-28 pt-12 sm:pt-16">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('agenda.index') }}" class="transition hover:text-[#e6c47d]">Agenda</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">{{ \Illuminate\Support\Str::limit($agenda->title, 48) }}</span>
            </nav>

            <div class="mt-8 max-w-4xl">
                <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                    Detail agenda
                </div>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[42px] sm:leading-[1.15]">
                    {{ $agenda->title }}
                </h1>

                <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2.5 text-[12px] font-semibold text-white/55 sm:text-[13px]">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path d="M8 3v4M16 3v4M3 10h18"/>
                        </svg>
                        <time datetime="{{ $agenda->event_date?->toDateString() }}">
                            {{ $agenda->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum tersedia' }}
                        </time>
                    </span>

                    @if ($startTime)
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3.5 2"/>
                            </svg>
                            <span>{{ $startTime }}@if ($endTime) &ndash; {{ $endTime }}@endif</span>
                        </span>
                    @endif

                    @if ($agenda->location)
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                <circle cx="12" cy="10" r="2.5"/>
                            </svg>
                            {{ $agenda->location }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="relative z-10 container-public -mt-16 pb-20">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.42fr)] lg:items-start">
            <article class="overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white shadow-[0_28px_80px_-45px_rgba(14,59,49,.5)]">
                @if ($agenda->image)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::url($agenda->image) }}"
                        alt="{{ $agenda->title }}"
                        class="aspect-[16/8] w-full object-cover"
                    >
                @endif

                <div class="p-6 sm:p-8">
                    <dl class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[#eee8dc] bg-[#fbf9f4] p-4">
                            <dt class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#a17f3e]">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M8 3v4M16 3v4M3 10h18"/>
                                </svg>
                                Tanggal
                            </dt>
                            <dd class="mt-2 text-[13px] font-extrabold leading-6 text-[#123b32]">
                                {{ $agenda->event_date?->translatedFormat('d F Y') ?? 'Belum tersedia' }}
                            </dd>
                        </div>

                        <div class="rounded-2xl border border-[#eee8dc] bg-[#fbf9f4] p-4">
                            <dt class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#a17f3e]">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M12 7v5l3.5 2"/>
                                </svg>
                                Waktu
                            </dt>
                            <dd class="mt-2 text-[13px] font-extrabold leading-6 text-[#123b32]">
                                @if ($startTime)
                                    {{ $startTime }}@if ($endTime) &ndash; {{ $endTime }}@endif
                                @else
                                    Menyusul
                                @endif
                            </dd>
                        </div>

                        <div class="rounded-2xl border border-[#eee8dc] bg-[#fbf9f4] p-4">
                            <dt class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#a17f3e]">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="2.5"/>
                                </svg>
                                Lokasi
                            </dt>
                            <dd class="mt-2 text-[13px] font-extrabold leading-6 text-[#123b32]">
                                {{ $agenda->location ?: 'Menyusul' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.14em] {{ $badge }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                            {{ $agenda->event_status_label ?: 'Akan Datang' }}
                        </span>

                        @if ($agenda->event_date?->isToday())
                            <span class="rounded-full border border-[#d8c99f] bg-[#fbf3df] px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[.14em] text-[#8a6a24]">
                                Berlangsung hari ini
                            </span>
                        @endif
                    </div>

                    <div class="mt-8 border-t border-[#f0ebe0] pt-7">
                        <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                            <span class="gold-rule"></span>
                            Deskripsi kegiatan
                        </div>

                        <h2 class="mt-3 text-xl font-extrabold tracking-[-0.02em] text-[#123b32]">
                            Tentang Agenda Ini
                        </h2>

                        <div class="mt-4 text-[15px] leading-8 text-[#4c5a55]">
                            @if (trim((string) $agenda->description) !== '')
                                {!! nl2br(e($agenda->description)) !!}
                            @else
                                <p class="text-[13px] italic text-[#8a948f]">Deskripsi agenda belum tersedia.</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-9 flex flex-col gap-3 border-t border-[#f0ebe0] pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('agenda.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M19 12H5m6 6-6-6 6-6"/>
                            </svg>
                            Kembali ke Agenda
                        </a>

                        <a href="{{ route('agenda.arsip') }}" class="inline-flex items-center justify-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                            Lihat Arsip Agenda
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </article>

            <aside class="space-y-5 lg:sticky lg:top-28">
                <div class="rounded-3xl border border-[#e7dfd0] bg-white p-6">
                    <div class="gold-rule"></div>

                    <h2 class="mt-4 text-[15px] font-extrabold text-[#123b32]">Agenda Lainnya</h2>

                    @if ($otherAgendas->isEmpty())
                        <p class="mt-3 rounded-2xl border border-dashed border-[#ddd0b6] bg-[#fbf9f4] px-4 py-6 text-center text-[12px] leading-6 text-[#6b7873]">
                            Belum ada agenda mendatang lainnya saat ini.
                        </p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($otherAgendas as $otherAgenda)
                                <a href="{{ route('agenda.show', $otherAgenda->slug) }}" class="group flex items-center gap-4 rounded-2xl border border-[#eee8dc] bg-[#fbf9f4] p-3.5 transition hover:-translate-y-0.5 hover:border-[#d3c29a] hover:bg-white hover:shadow-[0_18px_35px_-26px_rgba(14,59,49,.5)]">
                                    <span class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-[#0e3b31] text-white">
                                        <span class="text-[9px] font-extrabold uppercase tracking-[.12em] text-[#e6c47d]">
                                            {{ $otherAgenda->event_date?->translatedFormat('M') ?? '—' }}
                                        </span>
                                        <span class="text-[15px] font-extrabold leading-none">
                                            {{ $otherAgenda->event_date?->format('d') ?? '—' }}
                                        </span>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[13px] font-extrabold text-[#123b32] transition group-hover:text-[#a17f3e]">
                                            {{ $otherAgenda->title }}
                                        </span>
                                        <span class="mt-0.5 block truncate text-[11px] font-semibold text-[#8a948f]">
                                            {{ $otherAgenda->event_date?->translatedFormat('d M Y') ?? '—' }}@if ($otherAgenda->location) &middot; {{ $otherAgenda->location }}@endif
                                        </span>
                                    </span>

                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#ddd0b6] text-[#a17f3e] transition group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d]">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path d="M9 6l6 6-6 6"/>
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <a href="{{ route('agenda.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-[11px] font-extrabold text-[#0e3b31] transition hover:text-[#a17f3e]">
                            Semua agenda mendatang
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="rounded-3xl bg-[#0e3b31] p-6 text-white">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-[#e6c47d]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 7h18v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7Z"/>
                            <path d="M8 7V4h8v3M10 12h4"/>
                        </svg>
                    </div>

                    <h2 class="mt-4 text-[15px] font-extrabold">Dokumentasi agenda</h2>
                    <p class="mt-2 text-[12px] leading-6 text-white/60">
                        Lihat riwayat kegiatan yang telah berlangsung pada arsip agenda publik.
                    </p>

                    <a href="{{ route('agenda.arsip') }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-[#e6c47d] px-4 py-2.5 text-[12px] font-extrabold text-[#123b32] transition hover:bg-[#f0d696]">
                        Lihat Arsip Agenda
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>

                <div class="rounded-3xl border border-[#e7dfd0] bg-[#fbf9f4] p-6">
                    <h2 class="text-[13px] font-extrabold text-[#123b32]">Ingin mengikuti kegiatan?</h2>
                    <p class="mt-2 text-[12px] leading-6 text-[#6b7873]">
                        Pantau halaman agenda secara berkala atau sampaikan aspirasi Anda agar dapat dipertimbangkan dalam kegiatan DPRD.
                    </p>

                    <a href="{{ route('aspirasi.create') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-4 py-2.5 text-[12px] font-extrabold text-white transition hover:bg-[#082a23]">
                        Sampaikan Aspirasi
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>
            </aside>
        </div>
    </section>
@endsection
