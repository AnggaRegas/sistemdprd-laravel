<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e3b31">
    <meta name="description" content="Portal resmi aspirasi dan informasi DPRD Kota Parepare. Sampaikan aspirasi, pengaduan, dan temukan informasi layanan masyarakat dalam satu portal.">

    <title>Portal Aspirasi & Informasi | DPRD Kota Parepare</title>

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

        .hero-title {
            font-size: clamp(1.85rem, 4.6vw, 3rem);
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

        .gold-rule {
            width: 44px;
            height: 3px;
            border-radius: 999px;
            background: var(--gold);
        }

        .reveal {
            animation: reveal .7s cubic-bezier(.22, .61, .36, 1) both;
        }

        .reveal-delay-1 {
            animation-delay: .1s;
        }

        .reveal-delay-2 {
            animation-delay: .2s;
        }

        @keyframes reveal {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .reveal,
            .reveal-delay-1,
            .reveal-delay-2 {
                animation: none;
            }

            * {
                transition-duration: .01ms !important;
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased text-slate-800">

    <a href="#konten" class="skip-link">Lewati ke konten utama</a>

    <!-- TOP BAR -->
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

    <!-- NAVBAR -->
    <header
        x-data="{ menuOpen: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 8"
        class="sticky top-0 z-50"
    >
        <nav
            class="border-b border-[#e9e2d4] bg-white/95 backdrop-blur-xl transition-shadow duration-300"
            :class="scrolled ? 'shadow-[0_16px_50px_-32px_rgba(14,59,49,.55)]' : ''"
        >
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="flex h-[78px] items-center justify-between gap-6">

                    <!-- LOGO -->
                    <a href="#beranda" class="group flex shrink-0 items-center gap-3" aria-label="DPRD Kota Parepare - Beranda">
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

                    <!-- MENU DESKTOP -->
                    <div class="hidden items-center gap-8 lg:flex">
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="#beranda">Beranda</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="#layanan">Layanan</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="#informasi">Informasi</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="#alur">Alur Pengajuan</a>
                        <a class="nav-link text-[13px] font-semibold text-[#52605b] hover:text-[#0e3b31]" href="#faq">FAQ</a>
                        <a class="nav-link text-[13px] font-extrabold text-[#a17f3e] hover:text-[#0e3b31]" href="{{ route('aspirasi.create') }}">Aspirasi</a>
                    </div>

                    <!-- CTA DESKTOP -->
                    <div class="hidden lg:block">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="group inline-flex items-center gap-2 rounded-full bg-[#0e3b31] px-5 py-2.5 text-[13px] font-bold text-white transition hover:bg-[#082a23]">
                                Dashboard
                                <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="group inline-flex items-center gap-2 rounded-full border border-[#0e3b31]/20 px-5 py-2.5 text-[13px] font-bold text-[#0e3b31] transition hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-white">
                                Portal Petugas
                                <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        @endauth
                    </div>

                    <!-- TOGGLE MOBILE -->
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

            <!-- MENU MOBILE -->
            <div x-show="menuOpen" x-cloak x-transition.opacity class="border-t border-[#eee8dc] bg-white lg:hidden">
                <div class="mx-auto max-w-7xl px-5 py-4 sm:px-8">
                    <div class="flex flex-col">
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#beranda">Beranda</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#layanan">Layanan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#informasi">Informasi</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#alur">Alur Pengajuan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#faq">FAQ</a>
                        <a @click="menuOpen = false" class="rounded-xl bg-[#f6ecd7] px-4 py-3 text-sm font-extrabold text-[#7a5e28] transition hover:bg-[#f0e1c0]" href="{{ route('aspirasi.create') }}">Sampaikan Aspirasi</a>

                        @auth
                            <a href="{{ route('dashboard') }}" class="mt-3 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-center text-sm font-bold text-white">
                                Dashboard Petugas
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="mt-3 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-center text-sm font-bold text-white">
                                Portal Petugas
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="konten">

        <!-- HERO -->
        <section id="beranda" class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
            <div class="hero-grid absolute inset-0"></div>
            <div class="absolute -right-32 -top-40 h-[540px] w-[540px] rounded-full bg-[#c9a45c]/20 blur-[110px]"></div>
            <div class="absolute -bottom-32 left-[-8%] h-80 w-80 rounded-full bg-emerald-300/10 blur-3xl"></div>

            <div class="absolute right-[7%] top-24 hidden h-[430px] w-[430px] rounded-full border border-white/10 lg:block"></div>
            <div class="absolute right-[11%] top-32 hidden h-[350px] w-[350px] rounded-full border border-white/[0.06] lg:block"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 pb-36 pt-16 sm:px-8 sm:pt-20 lg:grid-cols-[1.06fr_.94fr] lg:gap-16 lg:pb-40 lg:pt-24">

                <!-- HERO TEXT -->
                <div class="max-w-2xl">
                    <div class="reveal inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        Portal Resmi DPRD Kota Parepare
                    </div>

                    <h1 class="hero-title reveal reveal-delay-1 mt-6 font-extrabold leading-[1.1] tracking-[-0.035em] text-white sm:mt-7">
                        Sampaikan Aspirasi &amp;<br>
                        Pengaduan Anda<br>
                        <span class="text-[#e6c47d]">Langsung ke Wakil Rakyat.</span>
                    </h1>

                    <p class="reveal reveal-delay-2 mt-6 max-w-xl text-[15px] leading-7 text-white/65 sm:text-base sm:leading-8">
                        Temukan informasi seputar DPRD Kota Parepare dan sampaikan aspirasi atau pengaduan Anda melalui satu portal yang mudah diakses.
                    </p>

                    <div class="reveal reveal-delay-2 mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('aspirasi.create') }}"
                           class="group inline-flex items-center justify-center gap-3 rounded-xl bg-[#e6c47d] px-6 py-3.5 text-sm font-extrabold text-[#123b32] shadow-[0_16px_36px_-16px_rgba(230,196,125,.55)] transition hover:-translate-y-0.5 hover:bg-[#f0d696]">
                            Sampaikan Aspirasi
                            <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>

                        <a href="#informasi"
                           class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/[0.04] px-6 py-3.5 text-sm font-bold text-white transition hover:border-white/35 hover:bg-white/10">
                            Jelajahi Informasi
                        </a>
                    </div>

                    <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-white/10 pt-6 text-[12px] font-medium text-white/55 sm:text-[13px]">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="m5 12 4 4L19 6"/>
                            </svg>
                            Mudah diakses
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="m5 12 4 4L19 6"/>
                            </svg>
                            Informasi terpadu
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="m5 12 4 4L19 6"/>
                            </svg>
                            Sesuai prosedur
                        </span>
                    </div>
                </div>

                <!-- HERO CARD -->
                <div class="relative mx-auto w-full max-w-[460px] lg:mr-0">
                    <div class="absolute -inset-3 rounded-[32px] border border-white/10"></div>

                    <div class="absolute -right-6 -top-14 z-10 hidden h-[74px] w-[74px] flex-col items-center justify-center rounded-full bg-[#e6c47d] text-center text-[9px] font-extrabold uppercase leading-[1.35] tracking-[.08em] text-[#123b32] shadow-xl sm:flex">
                        <svg class="mb-1 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                        Portal<br>Resmi
                    </div>

                    <div class="relative overflow-hidden rounded-[28px] bg-[#fbf9f4] p-5 text-[#16342c] shadow-[0_35px_90px_-25px_rgba(0,0,0,.55)] sm:p-7">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-[#c9a45c]/15 blur-2xl"></div>

                        <div class="relative flex items-start justify-between gap-4 border-b border-[#e9e2d4] pb-5">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">Layanan masyarakat</p>
                                <h2 class="mt-2 text-xl font-extrabold tracking-tight text-[#123b32] sm:text-[22px]">
                                    Ada yang ingin Anda sampaikan?
                                </h2>
                                <p class="mt-2 text-xs leading-5 text-[#6b7873]">
                                    Pilih layanan yang sesuai dengan kebutuhan Anda.
                                </p>
                            </div>

                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31] text-[#e6c47d]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8Z"/>
                                </svg>
                            </span>
                        </div>

                        <div class="relative mt-5 rounded-2xl border border-[#e7dfcd] bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[10px] font-extrabold uppercase tracking-[.15em] text-[#a17f3e]">Aspirasi &amp; Pengaduan</span>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wide text-[#0e3b31]">Baru</span>
                            </div>

                            <p class="mt-3 text-[13px] leading-6 text-[#71807a]">
                                Tuliskan aspirasi, usulan, atau pengaduan Anda untuk ditindaklanjuti sesuai prosedur.
                            </p>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-[#f0ebe0] pt-3.5">
                                <span class="flex items-center gap-1.5 text-[10px] font-semibold text-[#9a8f79]">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <rect x="4" y="10" width="16" height="10" rx="2"/>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                    Data dijaga kerahasiaannya
                                </span>

                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0e3b31] text-[#e6c47d]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <p class="relative mt-6 text-[10px] font-extrabold uppercase tracking-[.16em] text-[#a17f3e]">Layanan cepat</p>

                        <div class="relative mt-3 space-y-2.5">
                            <a href="{{ route('aspirasi.create') }}" class="group flex items-center gap-3.5 rounded-xl bg-white px-4 py-3.5 ring-1 ring-transparent transition hover:ring-[#d9c9a6]">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0e3b31]/5 text-[#0e3b31]">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[13px] font-bold text-[#16342c]">Pengajuan Aspirasi</strong>
                                    <span class="mt-0.5 block text-[10px] text-[#7c8984]">Sampaikan usulan dan pengaduan</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-[#a8b1ad] transition group-hover:translate-x-0.5 group-hover:text-[#0e3b31]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>

                            <a href="#informasi" class="group flex items-center gap-3.5 rounded-xl bg-white px-4 py-3.5 ring-1 ring-transparent transition hover:ring-[#d9c9a6]">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#c9a45c]/10 text-[#a17f3e]">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[13px] font-bold text-[#16342c]">Informasi DPRD</strong>
                                    <span class="mt-0.5 block text-[10px] text-[#7c8984]">Berita, agenda, dan profil lembaga</span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-[#a8b1ad] transition group-hover:translate-x-0.5 group-hover:text-[#0e3b31]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        </div>

                        <div class="relative mt-5 flex items-start gap-2.5 rounded-xl bg-[#f1ece0] p-3.5 text-[10px] leading-5 text-[#65736e]">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#a17f3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                            <p>Setiap aspirasi diperiksa dan ditindaklanjuti sesuai ketentuan yang berlaku.</p>
                        </div>
                    </div>

                    <div class="absolute -bottom-6 -left-4 hidden items-center gap-3 rounded-2xl bg-[#e6c47d] px-4 py-3 text-[#123b32] shadow-xl sm:flex">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#123b32] text-[#e6c47d]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <span class="leading-tight">
                            <span class="block text-[9px] font-extrabold uppercase tracking-[.13em] text-[#7a5e28]">Suara Anda</span>
                            <span class="mt-0.5 block text-xs font-extrabold">Prioritas kami</span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FITUR SINGKAT -->
        <section class="relative z-10 -mt-14">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="grid overflow-hidden rounded-2xl border border-[#e7dfd0] bg-white shadow-[0_24px_70px_-40px_rgba(14,59,49,.45)] sm:grid-cols-3">
                    <div class="flex items-center gap-4 border-b border-[#eee8dc] p-6 sm:border-b-0 sm:border-r sm:p-7">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/5 text-[#0e3b31]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3.5 2"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[14px] font-extrabold text-[#123b32]">Praktis diakses</h3>
                            <p class="mt-1 text-[12px] leading-5 text-[#75827d]">Informasi dari satu tempat</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-b border-[#eee8dc] p-6 sm:border-b-0 sm:border-r sm:p-7">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#c9a45c]/10 text-[#a17f3e]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[14px] font-extrabold text-[#123b32]">Informasi terarah</h3>
                            <p class="mt-1 text-[12px] leading-5 text-[#75827d]">Menu sesuai kebutuhan warga</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-6 sm:p-7">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/5 text-[#0e3b31]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/>
                                <circle cx="9.5" cy="7" r="4"/>
                                <path d="M19 8v6m3-3h-6"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-[14px] font-extrabold text-[#123b32]">Partisipasi warga</h3>
                            <p class="mt-1 text-[12px] leading-5 text-[#75827d]">Mendorong suara masyarakat</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- LAYANAN -->
        <section id="layanan" class="mx-auto max-w-7xl px-5 py-24 sm:px-8 lg:py-28">
            <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end lg:gap-16">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                        <span class="h-px w-8 bg-[#c9a45c]"></span>
                        Layanan utama
                    </div>

                    <h2 class="mt-4 text-3xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[40px]">
                        Apa yang bisa Anda lakukan?
                    </h2>
                </div>

                <p class="max-w-sm text-sm leading-7 text-[#6b7873] lg:pb-1 lg:text-right">
                    Pilih layanan yang sesuai. Kami susun informasi agar lebih mudah ditemukan dan dipahami.
                </p>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-3">
                <!-- LAYANAN 1 -->
                <article class="group relative overflow-hidden rounded-3xl bg-[#0e3b31] p-7 text-white transition duration-300 hover:-translate-y-1 hover:shadow-[0_30px_60px_-30px_rgba(14,59,49,.55)] sm:p-8">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#c9a45c]/20 blur-2xl transition group-hover:bg-[#c9a45c]/30"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 h-40 w-40 rounded-full border border-white/10"></div>

                    <div class="relative">
                        <div class="flex items-start justify-between">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-[#e6c47d] ring-1 ring-white/10">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                </svg>
                            </span>
                            <span class="text-[11px] font-extrabold tracking-[.18em] text-white/25">01</span>
                        </div>

                        <h3 class="mt-10 text-xl font-extrabold tracking-tight">Aspirasi Masyarakat</h3>
                        <p class="mt-3 text-[13px] leading-7 text-white/60">
                            Sampaikan saran, usulan pembangunan, atau hal yang perlu menjadi perhatian bersama.
                        </p>

                        <a href="{{ route('aspirasi.create') }}" class="mt-8 inline-flex items-center gap-2 text-[13px] font-extrabold text-[#e6c47d] transition hover:gap-3">
                            Mulai pengajuan
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </article>

                <!-- LAYANAN 2 -->
                <article class="group rounded-3xl border border-[#e7dfd0] bg-white p-7 transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)] sm:p-8">
                    <div class="flex items-start justify-between">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#c9a45c]/10 text-[#a17f3e]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>
                                <path d="M8 7h8M8 11h8"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-extrabold tracking-[.18em] text-[#c9b992]">02</span>
                    </div>

                    <h3 class="mt-10 text-xl font-extrabold tracking-tight text-[#123b32]">Informasi &amp; Berita</h3>
                    <p class="mt-3 text-[13px] leading-7 text-[#6b7873]">
                        Temukan kabar kegiatan, agenda, pengumuman, dan informasi resmi seputar DPRD.
                    </p>

                    <a href="#informasi" class="mt-8 inline-flex items-center gap-2 text-[13px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Lihat informasi
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </article>

                <!-- LAYANAN 3 -->
                <article class="group rounded-3xl border border-[#e7dfd0] bg-white p-7 transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)] sm:p-8">
                    <div class="flex items-start justify-between">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0e3b31]/5 text-[#0e3b31]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/>
                                <path d="M16 3h5v5M10 14 21 3"/>
                                <path d="M8 8h4"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-extrabold tracking-[.18em] text-[#c9b992]">03</span>
                    </div>

                    <h3 class="mt-10 text-xl font-extrabold tracking-tight text-[#123b32]">Pengajuan Layanan</h3>
                    <p class="mt-3 text-[13px] leading-7 text-[#6b7873]">
                        Pelajari prosedur dan persyaratan pengajuan yang tersedia melalui portal.
                    </p>

                    <a href="#alur" class="mt-8 inline-flex items-center gap-2 text-[13px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Pelajari alurnya
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </article>
            </div>
        </section>

        <!-- INFORMASI -->
        <section id="informasi" class="relative overflow-hidden border-y border-[#e7dfd0] bg-[#f1ecdf]">
            <div class="pointer-events-none absolute -bottom-16 right-0 select-none text-[180px] font-extrabold leading-none tracking-[-0.06em] text-[#0e3b31]/[0.035] sm:text-[240px]">
                DPRD
            </div>

            <div class="relative mx-auto grid max-w-7xl gap-14 px-5 py-24 sm:px-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:gap-20 lg:py-28">
                <div>
                    <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                        <span class="h-px w-8 bg-[#c9a45c]"></span>
                        Pusat informasi
                    </div>

                    <h2 class="mt-4 max-w-md text-3xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[40px]">
                        Kenali kegiatan dan peran DPRD
                    </h2>

                    <p class="mt-5 max-w-lg text-sm leading-7 text-[#63716c]">
                        Portal ini menjadi pintu masuk untuk mendapatkan informasi kelembagaan dan mengikuti perkembangan kegiatan DPRD Kota Parepare.
                    </p>

                    <div class="mt-7 space-y-3">
                        <div class="flex items-center gap-3 text-[13px] font-medium text-[#4e5e58]">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#0e3b31] text-white">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6"/>
                                </svg>
                            </span>
                            Informasi kelembagaan yang terstruktur
                        </div>
                        <div class="flex items-center gap-3 text-[13px] font-medium text-[#4e5e58]">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#0e3b31] text-white">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6"/>
                                </svg>
                            </span>
                            Publikasi kegiatan yang mudah diakses
                        </div>
                        <div class="flex items-center gap-3 text-[13px] font-medium text-[#4e5e58]">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#0e3b31] text-white">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6"/>
                                </svg>
                            </span>
                            Agenda dan dokumentasi dalam satu tempat
                        </div>
                    </div>

                    <a href="#layanan" class="group mt-8 inline-flex items-center gap-3 rounded-xl bg-[#0e3b31] px-5 py-3.5 text-[13px] font-bold text-white transition hover:bg-[#082a23]">
                        Jelajahi informasi
                        <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between px-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">Topik informasi</p>
                        <span class="text-[10px] font-semibold text-[#8b948f]">4 topik</span>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-[#ded4be] bg-white/75 shadow-[0_24px_70px_-50px_rgba(14,59,49,.45)] backdrop-blur">
                        <div class="divide-y divide-[#eee8dc]">
                            <div class="group flex items-center gap-4 px-5 py-5 transition hover:bg-white sm:px-6">
                                <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">01</span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[14px] font-extrabold text-[#123b32]">Profil DPRD</strong>
                                    <span class="mt-1 block text-[11px] leading-5 text-[#75827d]">Tugas, fungsi, dan struktur organisasi.</span>
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#e5ddcc] text-[#a8b1ad] transition group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="group flex items-center gap-4 px-5 py-5 transition hover:bg-white sm:px-6">
                                <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">02</span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[14px] font-extrabold text-[#123b32]">Berita &amp; Kegiatan</strong>
                                    <span class="mt-1 block text-[11px] leading-5 text-[#75827d]">Publikasi kegiatan terbaru dari DPRD.</span>
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#e5ddcc] text-[#a8b1ad] transition group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="group flex items-center gap-4 px-5 py-5 transition hover:bg-white sm:px-6">
                                <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">03</span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[14px] font-extrabold text-[#123b32]">Agenda Kegiatan</strong>
                                    <span class="mt-1 block text-[11px] leading-5 text-[#75827d]">Informasi jadwal kegiatan yang dipublikasikan.</span>
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#e5ddcc] text-[#a8b1ad] transition group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="group flex items-center gap-4 px-5 py-5 transition hover:bg-white sm:px-6">
                                <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">04</span>
                                <span class="min-w-0 flex-1">
                                    <strong class="block text-[14px] font-extrabold text-[#123b32]">Galeri Dokumentasi</strong>
                                    <span class="mt-1 block text-[11px] leading-5 text-[#75827d]">Dokumentasi foto kegiatan dan aktivitas lembaga.</span>
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#e5ddcc] text-[#a8b1ad] transition group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ALUR -->
        <section id="alur" class="mx-auto max-w-7xl px-5 py-24 sm:px-8 lg:py-28">
            <div class="mx-auto max-w-2xl text-center">
                <div class="gold-rule mx-auto"></div>

                <p class="mt-5 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                    Langkah mudah
                </p>

                <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[40px]">
                    Alur pengajuan aspirasi
                </h2>

                <p class="mt-4 text-sm leading-7 text-[#6b7873]">
                    Empat langkah sederhana untuk menyampaikan aspirasi atau pengaduan Anda.
                </p>
            </div>

            <div class="relative mt-14 grid gap-8 md:grid-cols-4 md:gap-6">
                <div class="absolute left-[12%] right-[12%] top-7 hidden border-t-2 border-dashed border-[#d8c99f] md:block"></div>

                <div class="relative text-center">
                    <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border-4 border-[#f7f4ec] bg-[#0e3b31] text-[15px] font-extrabold text-[#e6c47d] shadow-sm">
                        01
                    </div>
                    <h3 class="mt-5 text-[15px] font-extrabold text-[#123b32]">Pilih layanan</h3>
                    <p class="mx-auto mt-2 max-w-[220px] text-[12px] leading-6 text-[#6b7873]">
                        Tentukan jenis aspirasi atau pengajuan yang sesuai.
                    </p>
                </div>

                <div class="relative text-center">
                    <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border-4 border-[#f7f4ec] bg-[#0e3b31] text-[15px] font-extrabold text-[#e6c47d] shadow-sm">
                        02
                    </div>
                    <h3 class="mt-5 text-[15px] font-extrabold text-[#123b32]">Lengkapi data</h3>
                    <p class="mx-auto mt-2 max-w-[220px] text-[12px] leading-6 text-[#6b7873]">
                        Isi informasi yang dibutuhkan secara jelas dan benar.
                    </p>
                </div>

                <div class="relative text-center">
                    <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border-4 border-[#f7f4ec] bg-[#0e3b31] text-[15px] font-extrabold text-[#e6c47d] shadow-sm">
                        03
                    </div>
                    <h3 class="mt-5 text-[15px] font-extrabold text-[#123b32]">Kirim pengajuan</h3>
                    <p class="mx-auto mt-2 max-w-[220px] text-[12px] leading-6 text-[#6b7873]">
                        Periksa kembali informasi sebelum mengirimkannya.
                    </p>
                </div>

                <div class="relative text-center">
                    <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border-4 border-[#f7f4ec] bg-[#c9a45c] text-[15px] font-extrabold text-[#123b32] shadow-sm">
                        04
                    </div>
                    <h3 class="mt-5 text-[15px] font-extrabold text-[#123b32]">Tindak lanjut</h3>
                    <p class="mx-auto mt-2 max-w-[220px] text-[12px] leading-6 text-[#6b7873]">
                        Petugas memeriksa pengajuan sesuai prosedur yang berlaku.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="pengajuan" class="px-5 pb-24 sm:px-8 lg:pb-28">
            <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[32px] bg-[#0e3b31] px-6 py-16 text-center text-white sm:px-12 sm:py-20">
                <div class="hero-grid absolute inset-0"></div>
                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
                <div class="absolute -bottom-28 -left-16 h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>
                <div class="absolute left-1/2 top-1/2 hidden h-[460px] w-[460px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/[0.06] lg:block"></div>

                <div class="relative mx-auto max-w-2xl">
                    <p class="text-[10px] font-extrabold uppercase tracking-[.22em] text-[#e6c47d]">
                        Partisipasi Anda berarti
                    </p>

                    <h2 class="mt-4 text-3xl font-extrabold leading-tight tracking-[-0.03em] sm:text-[42px]">
                        Mari ikut membangun Kota Parepare.
                    </h2>

                    <p class="mx-auto mt-5 max-w-xl text-sm leading-7 text-white/60">
                        Sampaikan aspirasi dan pelajari informasi layanan melalui portal DPRD Kota Parepare.
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('aspirasi.create') }}"
                           class="group inline-flex w-full items-center justify-center gap-3 rounded-xl bg-[#e6c47d] px-6 py-3.5 text-sm font-extrabold text-[#123b32] transition hover:-translate-y-0.5 hover:bg-[#f0d696] sm:w-auto">
                            Sampaikan Aspirasi
                            <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                        <a href="#alur"
                           class="inline-flex w-full items-center justify-center rounded-xl border border-white/20 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/10 sm:w-auto">
                            Pelajari alurnya
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[11px] font-medium text-white/45">
                        <span>Tanpa biaya</span>
                        <span class="h-1 w-1 rounded-full bg-white/25"></span>
                        <span>Data terjaga</span>
                        <span class="h-1 w-1 rounded-full bg-white/25"></span>
                        <span>Ditindaklanjuti sesuai SOP</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="border-t border-[#e7dfd0] bg-[#f1ecdf]">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-24 sm:px-8 lg:grid-cols-[.8fr_1.2fr] lg:gap-20 lg:py-28">
                <div>
                    <div class="gold-rule"></div>

                    <p class="mt-5 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#a17f3e]">
                        Pusat bantuan
                    </p>

                    <h2 class="mt-3 text-3xl font-extrabold tracking-[-0.03em] text-[#123b32] sm:text-[40px]">
                        Pertanyaan umum
                    </h2>

                    <p class="mt-4 max-w-sm text-sm leading-7 text-[#6b7873]">
                        Temukan jawaban singkat seputar portal, aspirasi, dan proses tindak lanjut.
                    </p>

                    <div class="mt-8 rounded-2xl border border-[#ded4be] bg-white/70 p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#c9a45c]/10 text-[#a17f3e]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-[13px] font-extrabold text-[#123b32]">Baru pertama kali?</p>
                                <p class="mt-1 text-[11px] leading-5 text-[#75827d]">Lihat panduan singkat alur pengajuan.</p>
                            </div>
                        </div>

                        <a href="#alur" class="group mt-4 inline-flex items-center gap-2 text-[12px] font-extrabold text-[#0e3b31] transition hover:text-[#a17f3e]">
                            Baca panduan
                            <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div x-data="{ active: 1 }" class="border-t border-[#d9ceb7]">
                    <!-- FAQ 1 -->
                    <div class="border-b border-[#d9ceb7]">
                        <button
                            @click="active = active === 1 ? null : 1"
                            :aria-expanded="(active === 1).toString()"
                            aria-controls="faq-answer-1"
                            class="group flex w-full items-center gap-4 py-5 text-left">
                            <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">01</span>
                            <span class="flex-1 text-[14px] font-extrabold text-[#123b32]">Apa fungsi portal ini?</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#d9ceb7] transition group-hover:border-[#0e3b31]"
                                  :class="active === 1 ? 'bg-[#0e3b31] text-white border-[#0e3b31]' : 'text-[#0e3b31]'">
                                <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="active === 1 ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </span>
                        </button>

                        <div x-show="active === 1" x-transition.opacity id="faq-answer-1" class="pb-6 pl-9 pr-12 text-[13px] leading-7 text-[#63716c]">
                            Portal ini dirancang sebagai tempat untuk mengakses informasi DPRD Kota Parepare serta menemukan layanan aspirasi dan pengajuan masyarakat.
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="border-b border-[#d9ceb7]">
                        <button
                            @click="active = active === 2 ? null : 2"
                            :aria-expanded="(active === 2).toString()"
                            aria-controls="faq-answer-2"
                            class="group flex w-full items-center gap-4 py-5 text-left">
                            <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">02</span>
                            <span class="flex-1 text-[14px] font-extrabold text-[#123b32]">Bagaimana cara menyampaikan aspirasi?</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#d9ceb7] transition group-hover:border-[#0e3b31]"
                                  :class="active === 2 ? 'bg-[#0e3b31] text-white border-[#0e3b31]' : 'text-[#0e3b31]'">
                                <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="active === 2 ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </span>
                        </button>

                        <div x-show="active === 2" x-transition.opacity id="faq-answer-2" class="pb-6 pl-9 pr-12 text-[13px] leading-7 text-[#63716c]">
                            Pilih layanan Aspirasi Masyarakat, lengkapi formulir, lalu periksa kembali data yang diminta sebelum mengirim.
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="border-b border-[#d9ceb7]">
                        <button
                            @click="active = active === 3 ? null : 3"
                            :aria-expanded="(active === 3).toString()"
                            aria-controls="faq-answer-3"
                            class="group flex w-full items-center gap-4 py-5 text-left">
                            <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">03</span>
                            <span class="flex-1 text-[14px] font-extrabold text-[#123b32]">Apakah semua pengajuan langsung disetujui?</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#d9ceb7] transition group-hover:border-[#0e3b31]"
                                  :class="active === 3 ? 'bg-[#0e3b31] text-white border-[#0e3b31]' : 'text-[#0e3b31]'">
                                <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="active === 3 ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </span>
                        </button>

                        <div x-show="active === 3" x-transition.opacity id="faq-answer-3" class="pb-6 pl-9 pr-12 text-[13px] leading-7 text-[#63716c]">
                            Tidak. Pengajuan perlu diperiksa dan ditindaklanjuti sesuai ketentuan serta prosedur yang berlaku. Portal tidak menjamin setiap pengajuan disetujui.
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="border-b border-[#d9ceb7]">
                        <button
                            @click="active = active === 4 ? null : 4"
                            :aria-expanded="(active === 4).toString()"
                            aria-controls="faq-answer-4"
                            class="group flex w-full items-center gap-4 py-5 text-left">
                            <span class="text-[10px] font-extrabold tracking-[.16em] text-[#a17f3e]">04</span>
                            <span class="flex-1 text-[14px] font-extrabold text-[#123b32]">Apakah data yang saya sampaikan aman?</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#d9ceb7] transition group-hover:border-[#0e3b31]"
                                  :class="active === 4 ? 'bg-[#0e3b31] text-white border-[#0e3b31]' : 'text-[#0e3b31]'">
                                <svg class="h-3.5 w-3.5 transition-transform duration-300" :class="active === 4 ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </span>
                        </button>

                        <div x-show="active === 4" x-transition.opacity id="faq-answer-4" class="pb-6 pl-9 pr-12 text-[13px] leading-7 text-[#63716c]">
                            Data yang Anda sampaikan digunakan untuk keperluan tindak lanjut aspirasi dan pengaduan sesuai ketentuan yang berlaku.
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-[#082a23] text-white">
        <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.5fr_.7fr_.7fr_.9fr] lg:gap-12">
                <div>
                    <a href="#beranda" class="inline-flex items-center gap-3" aria-label="DPRD Kota Parepare - Beranda">
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
                    <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Jelajahi</h3>
                    <div class="mt-4 flex flex-col gap-3 text-[13px] text-white/55">
                        <a href="#beranda" class="transition hover:text-[#e6c47d]">Beranda</a>
                        <a href="#layanan" class="transition hover:text-[#e6c47d]">Layanan</a>
                        <a href="#informasi" class="transition hover:text-[#e6c47d]">Informasi</a>
                        <a href="#alur" class="transition hover:text-[#e6c47d]">Alur Pengajuan</a>
                        <a href="#faq" class="transition hover:text-[#e6c47d]">Pertanyaan Umum</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Portal</h3>
                    <div class="mt-4 flex flex-col gap-3 text-[13px] text-white/55">
                        @auth
                            <a href="{{ route('dashboard') }}" class="transition hover:text-[#e6c47d]">Dashboard Petugas</a>
                        @else
                            <a href="{{ route('login') }}" class="transition hover:text-[#e6c47d]">Portal Petugas</a>
                        @endauth
                        <a href="{{ route('aspirasi.create') }}" class="transition hover:text-[#e6c47d]">Sampaikan Aspirasi</a>
                        <a href="#alur" class="transition hover:text-[#e6c47d]">Panduan Pengajuan</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-[11px] font-extrabold uppercase tracking-[.18em] text-[#e6c47d]">Lokasi</h3>
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
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-5 text-[11px] text-white/35 sm:px-8 md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} DPRD Kota Parepare. Hak cipta dilindungi.</p>

                <div class="flex items-center gap-5">
                    <p>Portal informasi dan layanan masyarakat</p>
                    <a href="#beranda" class="inline-flex items-center gap-1.5 font-semibold text-white/50 transition hover:text-[#e6c47d]">
                        Kembali ke atas
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 19V5m-6 6 6-6 6 6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
