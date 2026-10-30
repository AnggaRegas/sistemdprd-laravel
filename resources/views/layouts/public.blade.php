<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e3b31">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Portal resmi aspirasi dan informasi DPRD Kota Parepare. Sampaikan aspirasi, masukan, dan laporan masyarakat secara daring.')">

    <title>@yield('title', 'Portal Aspirasi & Informasi | DPRD Kota Parepare')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --green: #0e3b31;
            --green-dark: #082a23;
            --green-deep: #061f1a;
            --gold: #c9a45c;
            --gold-light: #e6c47d;
            --cream: #f7f4ec;
            --cream-deep: #f1ecdf;
            --line: #e5ddcc;
            --ink: #123b32;
            --muted: #63716c;
        }

        html {
            scroll-padding-top: 96px;
        }

        body {
            background: var(--cream);
            font-family: 'Plus Jakarta Sans', 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        ::selection {
            background: var(--gold-light);
            color: var(--green);
        }

        .skip-link {
            position: absolute;
            left: 1rem;
            top: -5rem;
            z-index: 100;
            border-radius: .75rem;
            background: var(--gold-light);
            padding: .75rem 1.25rem;
            font-size: .875rem;
            font-weight: 800;
            color: var(--green);
            transition: top .2s ease;
        }

        .skip-link:focus {
            top: 1rem;
        }

        .hero-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .055) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .9), rgba(0, 0, 0, .35));
        }

        .gold-rule {
            width: 44px;
            height: 3px;
            border-radius: 999px;
            background: var(--gold);
        }

        .nav-link {
            position: relative;
            transition: color .2s ease;
        }

        .nav-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -7px;
            width: 0;
            height: 2px;
            border-radius: 999px;
            background: var(--gold);
            transition: width .25s ease;
        }

        .nav-link:hover::after,
        .nav-link:focus-visible::after {
            width: 100%;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased text-slate-800">

    <a href="#konten" class="skip-link">Lewati ke konten utama</a>

    <div class="bg-[#082a23] text-white/60">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-2.5 text-[11px] font-medium tracking-wide sm:px-8">
            <p class="flex items-center gap-2">
                <svg class="h-3.5 w-3.5 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                    <circle cx="12" cy="10" r="2.5"/>
                </svg>
                Kota Parepare, Sulawesi Selatan
            </p>

            <div class="flex items-center gap-5">
                <span class="hidden sm:inline">Portal resmi informasi &amp; aspirasi</span>

                <span class="flex items-center gap-2">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-300 opacity-60"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                    </span>
                    Layanan aktif
                </span>
            </div>
        </div>
    </div>

    <header
        x-data="{ menuOpen: false }"
        class="sticky top-0 z-50 border-b border-[#e9e2d4] bg-white/95 backdrop-blur-xl"
    >
        <nav aria-label="Navigasi utama">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="flex h-[78px] items-center justify-between gap-6">

                    <a href="{{ route('welcome') }}#beranda" class="group flex shrink-0 items-center gap-3" aria-label="DPRD Kota Parepare - Beranda">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0e3b31] text-[#e6c47d] shadow-sm transition group-hover:bg-[#082a23]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M3 21h18M5 21V8l7-4 7 4v13M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-3h4v3"/>
                            </svg>
                        </span>

                        <span class="leading-tight">
                            <span class="block text-[13px] font-extrabold tracking-[.04em] text-[#0e3b31] sm:text-sm">
                                DPRD KOTA PAREPARE
                            </span>
                            <span class="mt-1 block text-[9px] font-bold uppercase tracking-[.2em] text-[#a17f3e] sm:text-[10px]">
                                Portal Aspirasi &amp; Informasi
                            </span>
                        </span>
                    </a>

                    <div class="hidden items-center gap-7 lg:flex">
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="{{ route('welcome') }}#beranda">Beranda</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="{{ route('welcome') }}#layanan">Layanan</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="{{ route('welcome') }}#informasi">Informasi</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="{{ route('welcome') }}#alur">Alur Pengajuan</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="{{ route('welcome') }}#faq">FAQ</a>
                    </div>

                    <div class="hidden items-center gap-2.5 lg:flex">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center gap-2 rounded-full border border-[#0e3b31]/20 px-4 py-2.5 text-[12px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-white">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 rounded-full border border-[#0e3b31]/20 px-4 py-2.5 text-[12px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-white">
                                Portal Petugas
                            </a>
                        @endauth

                        <a href="{{ route('aspirasi.create') }}"
                           class="group inline-flex items-center gap-2 rounded-full bg-[#0e3b31] px-5 py-2.5 text-[13px] font-bold text-white transition hover:bg-[#082a23]">
                            Sampaikan Aspirasi
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>

                    <button
                        @click="menuOpen = !menuOpen"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-[#e5ddcc] text-[#0e3b31] transition hover:bg-[#f7f4ec] lg:hidden"
                        :aria-expanded="menuOpen.toString()"
                        aria-label="Buka menu navigasi">
                        <svg x-show="!menuOpen" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                        <svg x-show="menuOpen" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18"/>
                        </svg>
                    </button>

                </div>
            </div>

            <div x-show="menuOpen" x-cloak x-transition.opacity class="border-t border-[#eee8dc] bg-white lg:hidden">
                <div class="mx-auto max-w-7xl px-5 py-4 sm:px-8">
                    <div class="flex flex-col">
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="{{ route('welcome') }}#beranda">Beranda</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="{{ route('welcome') }}#layanan">Layanan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="{{ route('welcome') }}#informasi">Informasi</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="{{ route('welcome') }}#alur">Alur Pengajuan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="{{ route('welcome') }}#faq">FAQ</a>

                        <a href="{{ route('aspirasi.create') }}" class="mt-3 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-center text-sm font-bold text-white">
                            Sampaikan Aspirasi
                        </a>

                        @auth
                            <a href="{{ route('dashboard') }}" class="mt-2 rounded-xl border border-[#e5ddcc] px-4 py-3.5 text-center text-sm font-bold text-[#0e3b31]">
                                Dashboard Petugas
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="mt-2 rounded-xl border border-[#e5ddcc] px-4 py-3.5 text-center text-sm font-bold text-[#0e3b31]">
                                Portal Petugas
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="konten">
        @if (session('error'))
            <div class="mx-auto max-w-7xl px-5 pt-6 sm:px-8">
                <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm leading-6 text-red-700" role="alert">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5m0 3h.01"/>
                    </svg>
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-[#082a23] text-white">
        <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.5fr_.7fr_.8fr_.9fr] lg:gap-12">
                <div>
                    <a href="{{ route('welcome') }}#beranda" class="inline-flex items-center gap-3" aria-label="DPRD Kota Parepare - Beranda">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-[#e6c47d]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M3 21h18M5 21V8l7-4 7 4v13M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-3h4v3"/>
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block text-sm font-extrabold tracking-[.04em]">DPRD KOTA PAREPARE</span>
                            <span class="mt-1 block text-[10px] font-bold uppercase tracking-[.18em] text-[#e6c47d]/70">Portal Aspirasi &amp; Informasi</span>
                        </span>
                    </a>

                    <p class="mt-5 max-w-sm text-[13px] leading-7 text-white/50">
                        Media informasi dan partisipasi masyarakat untuk mendukung akses informasi publik yang lebih mudah.
                    </p>

                    <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3.5 py-2 text-[10px] font-semibold text-white/50">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                        Portal resmi pemerintah daerah
                    </div>
                </div>

                <div>
                    <h2 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Jelajahi</h2>
                    <div class="mt-4 flex flex-col gap-3 text-[13px] text-white/55">
                        <a href="{{ route('welcome') }}#beranda" class="transition hover:text-[#e6c47d]">Beranda</a>
                        <a href="{{ route('welcome') }}#layanan" class="transition hover:text-[#e6c47d]">Layanan</a>
                        <a href="{{ route('welcome') }}#informasi" class="transition hover:text-[#e6c47d]">Informasi</a>
                        <a href="{{ route('welcome') }}#alur" class="transition hover:text-[#e6c47d]">Alur Pengajuan</a>
                        <a href="{{ route('welcome') }}#faq" class="transition hover:text-[#e6c47d]">Pertanyaan Umum</a>
                    </div>
                </div>

                <div>
                    <h2 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Portal</h2>
                    <div class="mt-4 flex flex-col gap-3 text-[13px] text-white/55">
                        <a href="{{ route('aspirasi.create') }}" class="transition hover:text-[#e6c47d]">Sampaikan Aspirasi</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="transition hover:text-[#e6c47d]">Dashboard Petugas</a>
                        @else
                            <a href="{{ route('login') }}" class="transition hover:text-[#e6c47d]">Portal Petugas</a>
                        @endauth
                        <a href="{{ route('welcome') }}#alur" class="transition hover:text-[#e6c47d]">Panduan Pengajuan</a>
                    </div>
                </div>

                <div>
                    <h2 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Lokasi</h2>
                    <div class="mt-4 flex items-start gap-2.5 text-[13px] leading-6 text-white/55">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#e6c47d]/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                        <span>
                            Kota Parepare<br>
                            Sulawesi Selatan, Indonesia
                        </span>
                    </div>

                    <p class="mt-4 text-[11px] leading-5 text-white/35">
                        Kontak resmi belum tersedia pada portal ini. Gunakan formulir aspirasi untuk menyampaikan pesan kepada pengelola.
                    </p>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-5 text-[11px] text-white/35 sm:px-8 md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} DPRD Kota Parepare. Hak cipta dilindungi.</p>
                <p>Portal informasi dan layanan masyarakat</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
