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

        /* ====== HERO ATMOSPHERE ====== */
        .hero-aurora {
            background: radial-gradient(58% 52% at 16% 20%, rgba(23, 78, 63, .55), transparent 60%),
                radial-gradient(44% 46% at 78% 76%, rgba(12, 68, 53, .45), transparent 62%);
            animation: hero-drift-a 15s ease-in-out infinite;
        }

        .hero-orb {
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
            will-change: transform;
            animation: hero-drift-c 13s ease-in-out infinite;
        }

        .hero-part {
            position: absolute;
            height: 6px;
            width: 6px;
            border-radius: 9999px;
            background: var(--gold-light);
            box-shadow: 0 0 10px 3px rgba(230, 196, 125, .5);
            opacity: .55;
            pointer-events: none;
            will-change: transform, opacity;
        }

        .hero-p1 { animation: hero-p1 9s ease-in-out infinite; }
        .hero-p2 { animation: hero-p2 11s ease-in-out infinite; }
        .hero-p3 { animation: hero-p3 13s ease-in-out infinite; }
        .hero-p4 { animation: hero-p4 10s ease-in-out infinite; }
        .hero-p5 { animation: hero-p5 14s ease-in-out infinite; }

        @keyframes hero-drift-a {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(2%, -1.5%, 0);
            }
        }

        @keyframes hero-drift-b {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(-1.5%, 2%, 0);
            }
        }

        @keyframes hero-drift-c {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(1.6%, 1.2%, 0);
            }
        }

        @keyframes hero-p1 {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
                opacity: .5;
            }
            50% {
                transform: translate3d(46px, -54px, 0);
                opacity: 0.9;
            }
        }

        @keyframes hero-p2 {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
                opacity: .65;
            }
            33% {
                transform: translate3d(-52px, 38px, 0);
                opacity: .95;
            }
            66% {
                transform: translate3d(-16px, 96px, 0);
                opacity: .4;
            }
        }

        @keyframes hero-p3 {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
                opacity: .4;
            }
            50% {
                transform: translate3d(70px, 46px, 0);
                opacity: .85;
            }
        }

        @keyframes hero-p4 {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
                opacity: .6;
            }
            50% {
                transform: translate3d(-38px, 66px, 0);
                opacity: .95;
            }
        }

        @keyframes hero-p5 {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
                opacity: .55;
            }
            40% {
                transform: translate3d(230px, 96px, 0);
                opacity: .3;
            }
        }

        .hero-title {
            font-size: clamp(1.85rem, 4.4vw, 2.8rem);
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

        /* ====== HERO BACKGROUND: FOTO GEDUNG DPRD (FULL BLEED) ====== */
        .hero-photo {
            position: absolute;
            inset: 0;
            background-image: url("{{ asset('images/gedung-dprd-parepare.jpeg') }}");
            background-size: cover;
            background-position: 50% 60%;
            background-repeat: no-repeat;
            animation: hero-photo-reveal 1.8s cubic-bezier(.22, .61, .36, 1) both;
        }

        /* Overlay hijau tua: kiri lebih gelap, kanan menyatu dengan foto */
        .hero-photo-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(96deg,
                    rgba(8, 42, 35, .97) 0%,
                    rgba(8, 42, 35, .93) 24%,
                    rgba(8, 45, 38, .78) 40%,
                    rgba(9, 50, 42, .44) 56%,
                    rgba(9, 50, 42, .36) 74%,
                    rgba(7, 40, 34, .55) 100%),
                linear-gradient(180deg,
                    rgba(6, 31, 26, .66) 0%,
                    rgba(6, 31, 26, .22) 24%,
                    rgba(6, 31, 26, .16) 56%,
                    rgba(6, 31, 26, .74) 100%);
        }

        @media (max-width: 1023px) {
            .hero-photo {
                background-position: 55% 48%;
            }

            .hero-photo-overlay {
                background: linear-gradient(180deg,
                    rgba(7, 38, 32, .95) 0%,
                    rgba(7, 38, 32, .88) 42%,
                    rgba(8, 45, 38, .7) 64%,
                    rgba(8, 45, 38, .68) 80%,
                    rgba(6, 31, 26, .9) 100%);
            }
        }

        @media (max-width: 639px) {
            .hero-photo {
                background-position: 60% 40%;
            }
        }

        @keyframes hero-photo-reveal {
            from {
                opacity: 0;
                transform: scale(1.06);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ====== KARTU TOPIK INFORMASI ====== */
        .topic-card {
            transition: transform .3s ease, box-shadow .3s ease, background-color .3s ease;
        }

        .topic-card:hover {
            transform: translateY(-3px);
        }

        /* ====== INFORMASI DINAMIS & CAROUSEL DOKUMENTASI ====== */
        .info-card {
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 60px -38px rgba(14, 59, 49, .5);
        }

        .doc-carousel-viewport {
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
            overscroll-behavior-x: contain;
        }

        .doc-carousel-viewport::-webkit-scrollbar {
            display: none;
        }

        .doc-carousel-track {
            display: flex;
            gap: 1rem;
        }

        .doc-slide {
            flex: 0 0 calc((100% - 2rem) / 3);
            min-width: 0;
            scroll-snap-align: start;
        }

        .doc-slide-media {
            position: relative;
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            border-radius: 1rem;
            background: #082a23;
        }

        .doc-slide-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .5s ease;
        }

        .doc-slide-media:hover img,
        .doc-slide-media:focus-visible img {
            transform: scale(1.05);
        }

        .doc-slide-chip {
            position: absolute;
            left: .75rem;
            bottom: .75rem;
            max-width: calc(100% - 1.5rem);
            border-radius: 999px;
            background: rgba(6, 31, 26, .72);
            padding: .35rem .75rem;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            color: #f0dfb8;
            backdrop-filter: blur(6px);
        }

        .doc-carousel-btn {
            display: inline-flex;
            height: 2.5rem;
            width: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #ddd0b6;
            background: #fff;
            color: #0e3b31;
            transition: background-color .2s ease, color .2s ease, border-color .2s ease, opacity .2s ease;
        }

        .doc-carousel-btn:hover:not(:disabled) {
            border-color: #0e3b31;
            background: #0e3b31;
            color: #e6c47d;
        }

        .doc-carousel-btn:disabled {
            cursor: not-allowed;
            opacity: .35;
        }

        /* Carousel dapat digeser dengan mouse & touch: kursor grab + snap dimatikan saat drag */
        .doc-carousel-viewport {
            cursor: grab;
        }

        .doc-carousel-viewport.is-dragging {
            cursor: grabbing;
            scroll-behavior: auto;
            scroll-snap-type: none;
        }

        .doc-carousel-viewport.is-dragging img {
            pointer-events: none;
        }

        /* Accordion FAQ: transisi tinggi halus tanpa plugin tambahan */
        .faq-panel {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows .28s ease;
        }

        .faq-panel[data-open="true"] {
            grid-template-rows: 1fr;
        }

        .faq-panel > div {
            overflow: hidden;
        }

        @media (prefers-reduced-motion: reduce) {
            .faq-panel {
                transition: none;
            }
        }

        .doc-dot {
            height: 6px;
            width: 6px;
            border-radius: 999px;
            background: #cbbf9f;
            transition: width .25s ease, background-color .25s ease;
        }

        .doc-dot[aria-current="true"] {
            width: 22px;
            background: #a17f3e;
        }

        .doc-lightbox {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: rgba(6, 31, 26, .92);
            backdrop-filter: blur(4px);
        }

        .doc-lightbox.is-open {
            display: flex;
        }

        .doc-lightbox img {
            max-height: 82vh;
            max-width: min(1100px, 94vw);
            border-radius: .75rem;
            object-fit: contain;
            box-shadow: 0 30px 80px -20px rgba(0, 0, 0, .7);
        }

        @media (max-width: 1023px) {
            .doc-slide {
                flex-basis: calc((100% - 1rem) / 2);
            }
        }

        @media (max-width: 639px) {
            .doc-slide {
                flex-basis: calc(100% - 3rem);
            }
        }

        .hero-float-badge {
            animation: hero-float-badge 7s ease-in-out infinite;
        }

        @keyframes hero-float-badge {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        .hero-float-card {
            animation: hero-float-card 9s ease-in-out infinite;
        }

        @keyframes hero-float-card {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-6px);
            }
        }

        /* ====== REDUCED MOTION ====== */
        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .reveal,
            .reveal-delay-1,
            .reveal-delay-2 {
                animation: none;
            }

            .hero-photo,
            .hero-float-badge,
            .hero-float-card,
            .hero-p1,
            .hero-p2,
            .hero-p3,
            .hero-p4,
            .hero-p5,
            .hero-aurora,
            .hero-orb,
            .topic-card {
                animation: none !important;
                transition: none !important;
            }

            .doc-carousel-viewport {
                scroll-behavior: auto;
            }

            .hero-photo {
                transform: none;
                opacity: 1;
            }

            .hero-aurora {
                opacity: 1;
            }

            .hero-p1,
            .hero-p2,
            .hero-p3,
            .hero-p4,
            .hero-p5 {
                opacity: .55;
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
        <div class="container-public flex items-center justify-between gap-4 py-2.5 text-[11px] font-medium tracking-wide">
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
            <div class="container-public">
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

                    <!-- TOMBOL PORTAL PETUGAS & TOGGLE MOBILE -->
                    <div class="flex items-center gap-2.5">
                        @auth
                            <div class="relative group">
                                <a href="{{ route('dashboard') }}"
                                   class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-[#0e3b31] text-[#e6c47d] shadow-sm transition-all duration-200 hover:bg-[#082a23] hover:shadow focus:outline-none focus:ring-2 focus:ring-[#0e3b31]/20"
                                   title="Dashboard Petugas"
                                   aria-label="Dashboard Petugas">
                                    <svg class="h-5 w-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect width="7" height="9" x="3" y="3" rx="1"/>
                                        <rect width="7" height="5" x="14" y="3" rx="1"/>
                                        <rect width="7" height="9" x="14" y="12" rx="1"/>
                                        <rect width="7" height="5" x="3" y="16" rx="1"/>
                                    </svg>
                                    <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    </span>
                                </a>
                                <span class="pointer-events-none absolute -bottom-9 right-0 hidden whitespace-nowrap rounded-lg bg-[#0e3b31] px-2.5 py-1 text-[11px] font-bold text-white shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 translate-y-1 lg:block">
                                    Dashboard Petugas
                                </span>
                            </div>
                        @else
                            <div class="relative group">
                                <a href="{{ route('login') }}"
                                   class="flex h-10 w-10 items-center justify-center rounded-xl border border-[#0e3b31]/15 bg-white/80 text-[#0e3b31] shadow-xs backdrop-blur-sm transition-all duration-200 hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-[#e6c47d] hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0e3b31]/20"
                                   title="Portal Petugas"
                                   aria-label="Portal Petugas">
                                    <svg class="h-5 w-5 transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </a>
                                <span class="pointer-events-none absolute -bottom-9 right-0 hidden whitespace-nowrap rounded-lg bg-[#0e3b31] px-2.5 py-1 text-[11px] font-bold text-white shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 translate-y-1 lg:block">
                                    Portal Petugas
                                </span>
                            </div>
                        @endauth

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
            </div>

            <!-- MENU MOBILE -->
            <div x-show="menuOpen" x-cloak x-transition.opacity class="border-t border-[#eee8dc] bg-white lg:hidden">
                <div class="container-public py-4">
                    <div class="flex flex-col">
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#beranda">Beranda</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#layanan">Layanan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#informasi">Informasi</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#alur">Alur Pengajuan</a>
                        <a @click="menuOpen = false" class="rounded-xl px-4 py-3 text-sm font-semibold text-[#33443e] transition hover:bg-[#f7f4ec]" href="#faq">FAQ</a>
                        <a @click="menuOpen = false" class="rounded-xl bg-[#f6ecd7] px-4 py-3 text-sm font-extrabold text-[#7a5e28] transition hover:bg-[#f0e1c0]" href="{{ route('aspirasi.create') }}">Sampaikan Aspirasi</a>

                        @auth
                            <a href="{{ route('dashboard') }}" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-center text-sm font-bold text-white transition hover:bg-[#082a23]">
                                <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                                </svg>
                                <span>Dashboard Petugas</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-center text-sm font-bold text-white transition hover:bg-[#082a23]">
                                <svg class="h-4 w-4 text-[#e6c47d]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span>Portal Petugas</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="konten">
        @php
            $latestNews = $latestNews ?? collect();
            $upcomingAgendas = $upcomingAgendas ?? collect();
            $galleryPhotos = $galleryPhotos ?? collect();
        @endphp

        <!-- HERO -->
        <section id="beranda" class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
            <!-- FOTO GEDUNG DPRD SEBAGAI BACKGROUND HERO -->
            <div class="hero-photo" aria-hidden="true"></div>
            <div class="hero-photo-overlay" aria-hidden="true"></div>

            <!-- ATMOSFER: gradient bergerak, orb & glow lembut -->
            <div class="hero-aurora pointer-events-none absolute inset-[-10%]"></div>
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="hero-orb hero-float-badge -top-32 -right-24 h-[560px] w-[560px] bg-[#1c6b55]/25 blur-[120px]"></div>
                <div class="hero-orb hero-float-card -bottom-40 -left-24 h-[460px] w-[460px] bg-[#c9a45c]/10 blur-[110px]"></div>
                <div class="hero-orb hero-float-badge top-1/3 left-1/2 h-[300px] w-[300px] bg-emerald-300/[0.06] blur-[90px]"></div>
            </div>

            <!-- PARTIKEL CAHAYA EMAS -->
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <span class="hero-part hero-p1 left-[10%] top-[26%] h-1 w-1"></span>
                <span class="hero-part hero-p2 left-[26%] top-[68%] h-1.5 w-1.5 opacity-40"></span>
                <span class="hero-part hero-p3 left-[48%] top-[16%] h-[3px] w-[3px]"></span>
                <span class="hero-part hero-p4 right-[30%] top-[58%] h-1 w-1 opacity-70"></span>
                <span class="hero-part hero-p5 right-[12%] top-[34%] h-1.5 w-1.5"></span>
            </div>

            <div class="relative z-10 container-public grid items-center gap-12 pb-32 pt-14 sm:pt-20 lg:grid-cols-2 lg:gap-14 lg:pb-28 lg:pt-24">

                <!-- KOLOM KIRI: ASPIRASI -->
                <div class="relative max-w-2xl">
                    <div class="reveal inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                        Pusat Informasi
                    </div>

                    <h1 class="hero-title reveal reveal-delay-1 mt-6 font-extrabold leading-[1.12] tracking-[-0.035em] text-white sm:mt-7">
                        Sampaikan Aspirasi &amp; Pengaduan Anda<br>
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

                <!-- KOLOM KANAN: TOPIK INFORMASI -->
                <div id="informasi" class="relative z-10 w-full scroll-mt-32">
                    <div class="mb-5 flex items-center justify-between gap-3 px-1">
                        <div class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#e6c47d] sm:text-[11px]">
                            <span class="h-px w-7 bg-[#c9a45c]"></span>
                            Topik Informasi
                        </div>
                        <span class="rounded-full border border-white/15 bg-white/[0.07] px-2.5 py-0.5 text-[10px] font-semibold text-white/70 backdrop-blur">4 topik</span>
                    </div>

                    <div class="space-y-3.5">
                        <!-- KARTU 1 -->
                        <a href="#layanan" class="topic-card group flex items-center gap-4 rounded-2xl bg-[#fbf8f0] p-4 shadow-[0_20px_45px_-26px_rgba(0,0,0,.7)] ring-1 ring-black/[0.04] hover:bg-white sm:gap-5 sm:p-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/[0.07] text-[#0e3b31] transition group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d] sm:h-12 sm:w-12">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-4h6v4M9 11h.01M15 11h.01"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold tracking-[.18em] text-[#a17f3e]">01</span>
                                    <span class="h-px w-4 bg-[#c9a45c]/50"></span>
                                </span>
                                <strong class="mt-0.5 block text-[15px] font-extrabold text-[#123b32]">Profil DPRD</strong>
                                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-5 text-[#6b7873]">Tugas, fungsi, dan struktur organisasi.</span>
                            </span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#ddd0b6] bg-white/70 text-[#a17f3e] transition group-hover:translate-x-0.5 group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        <!-- KARTU 2 -->
                        <a href="#berita" class="topic-card group flex items-center gap-4 rounded-2xl bg-[#fbf8f0] p-4 shadow-[0_20px_45px_-26px_rgba(0,0,0,.7)] ring-1 ring-black/[0.04] hover:bg-white sm:gap-5 sm:p-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/[0.07] text-[#0e3b31] transition group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d] sm:h-12 sm:w-12">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                                    <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                                    <path d="M7.5 9h8M7.5 13h8M7.5 17h5"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold tracking-[.18em] text-[#a17f3e]">02</span>
                                    <span class="h-px w-4 bg-[#c9a45c]/50"></span>
                                </span>
                                <strong class="mt-0.5 block text-[15px] font-extrabold text-[#123b32]">Berita &amp; Kegiatan</strong>
                                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-5 text-[#6b7873]">Publikasi kegiatan terbaru dari DPRD.</span>
                            </span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#ddd0b6] bg-white/70 text-[#a17f3e] transition group-hover:translate-x-0.5 group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        <!-- KARTU 3 -->
                        <a href="#agenda" class="topic-card group flex items-center gap-4 rounded-2xl bg-[#fbf8f0] p-4 shadow-[0_20px_45px_-26px_rgba(0,0,0,.7)] ring-1 ring-black/[0.04] hover:bg-white sm:gap-5 sm:p-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/[0.07] text-[#0e3b31] transition group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d] sm:h-12 sm:w-12">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M8 3v4M16 3v4M3 10h18M8 14h2M14 14h2M8 18h2"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold tracking-[.18em] text-[#a17f3e]">03</span>
                                    <span class="h-px w-4 bg-[#c9a45c]/50"></span>
                                </span>
                                <strong class="mt-0.5 block text-[15px] font-extrabold text-[#123b32]">Agenda Kegiatan</strong>
                                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-5 text-[#6b7873]">Informasi jadwal kegiatan yang dipublikasikan.</span>
                            </span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#ddd0b6] bg-white/70 text-[#a17f3e] transition group-hover:translate-x-0.5 group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </a>

                        <!-- KARTU 4 -->
                        <a href="#dokumentasi" class="topic-card group flex items-center gap-4 rounded-2xl bg-[#fbf8f0] p-4 shadow-[0_20px_45px_-26px_rgba(0,0,0,.7)] ring-1 ring-black/[0.04] hover:bg-white sm:gap-5 sm:p-5">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0e3b31]/[0.07] text-[#0e3b31] transition group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d] sm:h-12 sm:w-12">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <circle cx="9" cy="10" r="1.6"/>
                                    <path d="m4 18 5-5 4 4 3-3 4 4"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold tracking-[.18em] text-[#a17f3e]">04</span>
                                    <span class="h-px w-4 bg-[#c9a45c]/50"></span>
                                </span>
                                <strong class="mt-0.5 block text-[15px] font-extrabold text-[#123b32]">Galeri Dokumentasi</strong>
                                <span class="mt-0.5 line-clamp-2 block text-[12px] leading-5 text-[#6b7873]">Dokumentasi foto kegiatan dan aktivitas lembaga.</span>
                            </span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#ddd0b6] bg-white/70 text-[#a17f3e] transition group-hover:translate-x-0.5 group-hover:border-[#0e3b31] group-hover:bg-[#0e3b31] group-hover:text-[#e6c47d]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- FITUR SINGKAT -->
        <section class="relative z-10 -mt-14">
            <div class="container-public">
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
        <section id="layanan" class="container-public py-24 lg:py-28">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between sm:gap-12">
                <x-public.section-header
                    eyebrow="Layanan utama"
                    title="Apa yang bisa Anda lakukan?"
                />

                <p class="max-w-sm shrink-0 text-sm leading-7 text-[#6b7873] sm:text-right">
                    Pilih layanan yang sesuai. Kami susun informasi agar lebih mudah ditemukan dan dipahami.
                </p>
            </div>

            <div class="mt-12 grid gap-5 md:grid-cols-3">
                <!-- LAYANAN 1 -->
                <article class="group relative flex flex-col overflow-hidden rounded-3xl bg-[#0e3b31] p-7 text-white transition duration-300 hover:-translate-y-1 hover:shadow-[0_30px_60px_-30px_rgba(14,59,49,.55)] sm:p-8">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#c9a45c]/20 blur-2xl transition group-hover:bg-[#c9a45c]/30"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-10 h-40 w-40 rounded-full border border-white/10"></div>

                    <div class="relative flex flex-1 flex-col">
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
                        <p class="mt-3 flex-1 text-[13px] leading-7 text-white/60">
                            Sampaikan saran, usulan pembangunan, atau hal yang perlu menjadi perhatian bersama.
                        </p>

                        <a href="{{ route('aspirasi.create') }}" class="mt-auto inline-flex items-center gap-2 pt-8 text-[13px] font-extrabold text-[#e6c47d] transition hover:gap-3">
                            Mulai pengajuan
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </article>

                <!-- LAYANAN 2 -->
                <article class="group flex flex-col rounded-3xl border border-[#e7dfd0] bg-white p-7 transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)] sm:p-8">
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
                    <p class="mt-3 flex-1 text-[13px] leading-7 text-[#6b7873]">
                        Temukan kabar kegiatan, agenda, pengumuman, dan informasi resmi seputar DPRD.
                    </p>

                    <a href="#informasi" class="mt-auto inline-flex items-center gap-2 pt-8 text-[13px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Lihat informasi
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </article>

                <!-- LAYANAN 3 -->
                <article class="group flex flex-col rounded-3xl border border-[#e7dfd0] bg-white p-7 transition duration-300 hover:-translate-y-1 hover:border-[#d3c29a] hover:shadow-[0_30px_60px_-35px_rgba(14,59,49,.4)] sm:p-8">
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
                    <p class="mt-3 flex-1 text-[13px] leading-7 text-[#6b7873]">
                        Pelajari prosedur dan persyaratan pengajuan yang tersedia melalui portal.
                    </p>

                    <a href="#alur" class="mt-auto inline-flex items-center gap-2 pt-8 text-[13px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                        Pelajari alurnya
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </article>
            </div>
        </section>

        <!-- BERITA & KEGIATAN -->
        <section id="berita" class="container-public scroll-mt-28 py-24 lg:py-28">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between sm:gap-12">
                <x-public.section-header
                    eyebrow="Berita & Kegiatan"
                    title="Kabar terbaru dari DPRD"
                    description="Publikasi kegiatan, pengumuman, dan informasi resmi DPRD Kota Parepare."
                />

                <a href="{{ route('berita.index') }}"
                   class="inline-flex w-fit shrink-0 items-center gap-2 rounded-xl border border-[#0e3b31]/15 bg-white px-5 py-3 text-[13px] font-extrabold text-[#0e3b31] transition hover:-translate-y-0.5 hover:border-[#0e3b31] hover:bg-[#0e3b31] hover:text-[#e6c47d]">
                    Lihat Semua Berita
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                    </svg>
                </a>
            </div>

            @if ($latestNews->isEmpty())
                <div class="mt-12 rounded-3xl border border-dashed border-[#d8c99f] bg-white/70 px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0e3b31]/5 text-[#0e3b31]">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                            <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                            <path d="M7.5 9h8M7.5 13h8M7.5 17h5"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">Belum ada berita terpublikasi</h3>
                    <p class="mx-auto mt-2 max-w-md text-[13px] leading-6 text-[#6b7873]">
                        Berita yang dipublikasikan melalui dashboard akan tampil otomatis di bagian ini.
                    </p>
                </div>
            @else
                <div class="mt-12 cards-grid">
                    @foreach ($latestNews as $item)
                        <article class="info-card group flex flex-col overflow-hidden rounded-3xl border border-[#e7dfd0] bg-white">
                            <a href="{{ route('berita.show', $item->slug) }}" class="block aspect-[16/10] overflow-hidden bg-[#0e3b31]/5" aria-label="{{ $item->title }}">
                                @if ($item->thumbnail)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item->thumbnail) }}"
                                         alt="{{ $item->title }}"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                         loading="lazy" decoding="async">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-[#0e3b31]/25">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                            <path d="M4 5h13a2 2 0 0 1 2 2v12H6a2 2 0 0 1-2-2V5Z"/>
                                            <path d="M19 8h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-1"/>
                                        </svg>
                                    </span>
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col p-6">
                                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-[.12em]">
                                    <span class="rounded-full bg-[#f6ecd7] px-2.5 py-1 text-[#7a5e28]">
                                        {{ \App\Models\LandingNews::CATEGORIES[$item->category] ?? ($item->category ? ucfirst($item->category) : 'Berita') }}
                                    </span>
                                    <span class="text-[#98a29d]">{{ $item->published_at?->translatedFormat('d M Y') }}</span>
                                </div>

                                <h3 class="mt-3 min-h-[3rem] text-[16px] font-extrabold leading-6 text-[#123b32]">
                                    <a href="{{ route('berita.show', $item->slug) }}" class="line-clamp-2 transition hover:text-[#a17f3e]">
                                        {{ $item->title }}
                                    </a>
                                </h3>

                                <p class="mt-2 min-h-[3rem] text-[12.5px] leading-6 text-[#6b7873] line-clamp-3">
                                    {{ \Illuminate\Support\Str::limit($item->excerpt ?: $item->content, 160) }}
                                </p>

                                <a href="{{ route('berita.show', $item->slug) }}"
                                   class="mt-auto inline-flex items-center gap-2 pt-5 text-[12px] font-extrabold text-[#0e3b31] transition hover:gap-3 hover:text-[#a17f3e]">
                                    Baca Selengkapnya
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- AGENDA KEGIATAN -->
        <section id="agenda" class="border-y border-[#e7dfd0] bg-[#f1ecdf]">
            <div class="container-public scroll-mt-28 py-24 lg:py-28">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between sm:gap-12">
                    <x-public.section-header
                        eyebrow="Agenda Kegiatan"
                        title="Jadwal kegiatan mendatang"
                        description="Ikuti agenda resmi DPRD Kota Parepare yang telah dipublikasikan."
                    />

                    <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                        <a href="{{ route('agenda.arsip') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-[#0e3b31]/15 bg-white px-5 py-3 text-[13px] font-extrabold text-[#0e3b31] transition hover:-translate-y-0.5 hover:border-[#0e3b31]">
                            Arsip Agenda
                        </a>
                        <a href="{{ route('agenda.index') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-[#e6c47d] transition hover:-translate-y-0.5 hover:bg-[#082a23]">
                            Lihat Semua Agenda
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                @if ($upcomingAgendas->isEmpty())
                    <div class="mt-12 rounded-3xl border border-dashed border-[#d8c99f] bg-white/70 px-6 py-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0e3b31]/5 text-[#0e3b31]">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2"/>
                                <path d="M8 3v4M16 3v4M3 10h18M8 14h2M14 14h2M8 18h2"/>
                            </svg>
                        </span>
                        <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">Belum ada agenda mendatang</h3>
                        <p class="mx-auto mt-2 max-w-md text-[13px] leading-6 text-[#6b7873]">
                            Agenda yang dipublikasikan akan muncul di sini secara otomatis.
                        </p>
                    </div>
                @else
                    <div class="mt-12 cards-grid-2">
                        @foreach ($upcomingAgendas as $agenda)
                            @php
                                $agendaClasses = match ($agenda->event_status) {
                                    \App\Models\LandingAgenda::EVENT_ONGOING => 'bg-amber-50 text-amber-700 border-amber-200',
                                    \App\Models\LandingAgenda::EVENT_COMPLETED => 'bg-slate-100 text-slate-600 border-slate-200',
                                    \App\Models\LandingAgenda::EVENT_CANCELLED => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                };
                            @endphp

                            <article class="info-card flex h-full gap-4 rounded-2xl border border-[#e2d9c5] bg-white p-5 sm:gap-5 sm:p-6">
                                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-[#0e3b31] text-white">
                                    <span class="text-xl font-extrabold leading-none">{{ $agenda->event_date?->format('d') }}</span>
                                    <span class="mt-1 text-[10px] font-bold uppercase tracking-[.14em] text-[#e6c47d]">
                                        {{ $agenda->event_date?->translatedFormat('M') }}
                                    </span>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $agendaClasses }}">
                                            {{ $agenda->event_status_label }}
                                        </span>
                                        <span class="text-[11px] font-semibold text-[#98a29d]">
                                            {{ substr((string) $agenda->start_time, 0, 5) }} WITA
                                        </span>
                                    </div>

                                    <h3 class="mt-2 min-h-[3rem] text-[15px] font-extrabold leading-6 text-[#123b32]">
                                        <a href="{{ route('agenda.show', $agenda->slug) }}" class="line-clamp-2 transition hover:text-[#a17f3e]">
                                            {{ $agenda->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-1 flex items-center gap-1.5 text-[12px] text-[#6b7873]">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-[#a17f3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                                            <circle cx="12" cy="10" r="2.5"/>
                                        </svg>
                                        <span class="truncate">{{ $agenda->location }}</span>
                                    </p>

                                    <a href="{{ route('agenda.show', $agenda->slug) }}"
                                       class="mt-auto inline-flex items-center gap-1.5 pt-3 text-[11.5px] font-extrabold text-[#0e3b31] transition hover:gap-2.5 hover:text-[#a17f3e]">
                                        Detail Agenda
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path d="M5 12h14m-6-6 6 6-6 6"/>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <!-- GALERI DOKUMENTASI -->
        <section id="dokumentasi" class="container-public scroll-mt-28 py-24 lg:py-28">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between sm:gap-12">
                <x-public.section-header
                    eyebrow="Galeri Dokumentasi"
                    title="Dokumentasi kegiatan lembaga"
                    description="Jelajahi foto kegiatan DPRD Kota Parepare. Geser carousel untuk melihat dokumentasi lainnya."
                />

                <a href="{{ route('dokumentasi.index') }}"
                   class="inline-flex w-fit shrink-0 items-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-[#e6c47d] transition hover:-translate-y-0.5 hover:bg-[#082a23]">
                    Lihat Semua Dokumentasi
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                    </svg>
                </a>
            </div>

            @if ($galleryPhotos->isEmpty())
                <div class="mt-12 rounded-3xl border border-dashed border-[#d8c99f] bg-white/70 px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0e3b31]/5 text-[#0e3b31]">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <circle cx="9" cy="10" r="1.6"/>
                            <path d="m4 18 5-5 4 4 3-3 4 4"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-extrabold text-[#123b32]">Belum ada foto dokumentasi</h3>
                    <p class="mx-auto mt-2 max-w-md text-[13px] leading-6 text-[#6b7873]">
                        Foto dari album yang dipublikasikan akan tampil pada carousel ini.
                    </p>
                    <a href="{{ route('dokumentasi.index') }}"
                       class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[12px] font-extrabold text-[#e6c47d] transition hover:bg-[#082a23]">
                        Buka Galeri Dokumentasi
                    </a>
                </div>
            @else
                <div class="mt-10" data-doc-carousel>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-[11px] font-semibold text-[#8a958f]">
                            {{ $galleryPhotos->count() }} foto terbaru · geser untuk menjelajah
                        </p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="doc-carousel-btn" data-carousel-prev aria-label="Foto sebelumnya">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m15 18-6-6 6-6"/>
                                </svg>
                            </button>
                            <button type="button" class="doc-carousel-btn" data-carousel-next aria-label="Foto berikutnya">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="doc-carousel-viewport" data-carousel-viewport tabindex="0" role="region" aria-label="Carousel dokumentasi kegiatan DPRD">
                        <div class="doc-carousel-track" data-carousel-track>
                            @foreach ($galleryPhotos as $photo)
                                @php
                                    $photoTitle = $photo['caption'] ?: $photo['gallery_title'];
                                    $photoMeta = collect([
                                        $photo['date']?->translatedFormat('d M Y'),
                                        $photo['location'] ?: null,
                                    ])->filter()->implode(' · ');
                                @endphp

                                <article class="doc-slide flex flex-col">
                                    <button type="button"
                                            class="doc-slide-media"
                                            data-lightbox-src="{{ $photo['url'] }}"
                                            data-lightbox-caption="{{ $photoTitle }}"
                                            aria-label="Perbesar foto: {{ $photoTitle }}">
                                        <img src="{{ $photo['url'] }}"
                                             alt="{{ $photoTitle }}"
                                             loading="lazy" decoding="async">
                                        <span class="doc-slide-chip block truncate">{{ $photo['gallery_title'] }}</span>
                                    </button>

                                    <div class="mt-3 flex flex-1 items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-2 min-h-[2.5rem] text-[13px] font-bold leading-5 text-[#123b32]">
                                                {{ $photoTitle }}
                                            </p>
                                            <p class="mt-1 truncate text-[11px] text-[#75827d]">
                                                {{ $photoMeta }}
                                            </p>
                                        </div>

                                        @if ($photo['gallery_slug'])
                                            <a href="{{ route('dokumentasi.show', $photo['gallery_slug']) }}"
                                               class="mt-auto shrink-0 text-[11px] font-extrabold text-[#a17f3e] transition hover:text-[#0e3b31]">
                                                Album
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-1.5" data-carousel-dots aria-label="Indikator posisi carousel"></div>
                        <p class="text-[11px] text-[#98a29d]">Klik foto untuk memperbesar</p>
                    </div>
                </div>
            @endif
        </section>

        <!-- ALUR -->
        <section id="alur" class="container-public py-24 lg:py-28">
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
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="hero-orb hero-float-badge -top-24 -right-16 h-72 w-72 bg-[#c9a45c]/20 blur-3xl"></div>
                    <div class="hero-orb hero-float-card -bottom-28 -left-16 h-72 w-72 bg-emerald-300/10 blur-3xl"></div>
                    <span class="hero-part hero-p1 left-[18%] top-[30%]" style="width:5px;height:5px;"></span>
                    <span class="hero-part hero-p3 right-[24%] top-[62%]" style="width:4px;height:4px;"></span>
                </div>

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
            <div class="container-public grid gap-12 py-24 lg:grid-cols-[.8fr_1.2fr] lg:gap-20 lg:py-28">
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

                        <div id="faq-answer-1" class="faq-panel" data-open="true" :data-open="(active === 1).toString()">
                            <div>
                                <p class="pb-6 pl-9 pr-6 text-[13px] leading-7 text-[#63716c] sm:pr-12">
                                    Portal ini dirancang sebagai tempat untuk mengakses informasi DPRD Kota Parepare serta menemukan layanan aspirasi dan pengajuan masyarakat.
                                </p>
                            </div>
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

                        <div id="faq-answer-2" class="faq-panel" :data-open="(active === 2).toString()">
                            <div>
                                <p class="pb-6 pl-9 pr-6 text-[13px] leading-7 text-[#63716c] sm:pr-12">
                                    Pilih layanan Aspirasi Masyarakat, lengkapi formulir, lalu periksa kembali data yang diminta sebelum mengirim.
                                </p>
                            </div>
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

                        <div id="faq-answer-3" class="faq-panel" :data-open="(active === 3).toString()">
                            <div>
                                <p class="pb-6 pl-9 pr-6 text-[13px] leading-7 text-[#63716c] sm:pr-12">
                                    Tidak. Pengajuan perlu diperiksa dan ditindaklanjuti sesuai ketentuan serta prosedur yang berlaku. Portal tidak menjamin setiap pengajuan disetujui.
                                </p>
                            </div>
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

                        <div id="faq-answer-4" class="faq-panel" :data-open="(active === 4).toString()">
                            <div>
                                <p class="pb-6 pl-9 pr-6 text-[13px] leading-7 text-[#63716c] sm:pr-12">
                                    Data yang Anda sampaikan digunakan untuk keperluan tindak lanjut aspirasi dan pengaduan sesuai ketentuan yang berlaku.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-[#082a23] text-white">
        <div class="container-public py-16">
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
            <div class="container-public flex flex-col gap-3 py-5 text-[11px] text-white/35 md:flex-row md:items-center md:justify-between">
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
    <!-- LIGHTBOX DOKUMENTASI -->
    <div id="docLightbox" class="doc-lightbox" role="dialog" aria-modal="true" aria-label="Pratinjau foto dokumentasi" aria-hidden="true">
        <button type="button"
                class="absolute right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20"
                data-lightbox-close
                aria-label="Tutup pratinjau">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>

        <button type="button"
                class="absolute left-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20 sm:left-6"
                data-lightbox-prev
                aria-label="Foto sebelumnya">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </button>

        <button type="button"
                class="absolute right-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20 sm:right-6"
                data-lightbox-next
                aria-label="Foto berikutnya">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="m9 6 6 6-6 6"/>
            </svg>
        </button>

        <figure class="relative z-10 flex max-h-full flex-col items-center gap-3">
            <img src="" alt="" data-lightbox-image>
            <figcaption class="max-w-2xl text-center text-[12px] font-medium leading-5 text-white/70" data-lightbox-caption></figcaption>
            <p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#e6c47d]/80" data-lightbox-counter></p>
        </figure>
    </div>

    <script>
        (function () {
            'use strict';

            /* ===== Carousel dokumentasi: 3 foto desktop, 2 tablet, 1 mobile ===== */
            var root = document.querySelector('[data-doc-carousel]');

            if (root) {
                var viewport = root.querySelector('[data-carousel-viewport]');
                var track = root.querySelector('[data-carousel-track]');
                var prevButton = root.querySelector('[data-carousel-prev]');
                var nextButton = root.querySelector('[data-carousel-next]');
                var dotsWrap = root.querySelector('[data-carousel-dots]');
                var slides = track ? Array.prototype.slice.call(track.children) : [];
                var activeIndex = 0;

                if (viewport && track && slides.length) {
                    var perView = function () {
                        if (window.matchMedia('(max-width: 639px)').matches) {
                            return 1;
                        }

                        if (window.matchMedia('(max-width: 1023px)').matches) {
                            return 2;
                        }

                        return 3;
                    };

                    var gap = function () {
                        var styles = window.getComputedStyle(track);
                        return parseFloat(styles.columnGap || styles.gap || '0') || 0;
                    };

                    var step = function () {
                        return slides[0].getBoundingClientRect().width + gap();
                    };

                    var maxIndex = function () {
                        return Math.max(0, slides.length - perView());
                    };

                    var goTo = function (index) {
                        var target = Math.min(Math.max(index, 0), maxIndex());
                        viewport.scrollTo({ left: target * step(), behavior: 'smooth' });
                    };

                    var buildDots = function () {
                        if (!dotsWrap) {
                            return;
                        }

                        dotsWrap.innerHTML = '';

                        if (maxIndex() + 1 <= 1) {
                            return;
                        }

                        for (var i = 0; i <= maxIndex(); i++) {
                            (function (index) {
                                var dot = document.createElement('button');
                                dot.type = 'button';
                                dot.className = 'doc-dot';
                                dot.setAttribute('aria-label', 'Ke tampilan foto ' + (index + 1));
                                dot.addEventListener('click', function () {
                                    goTo(index);
                                });
                                dotsWrap.appendChild(dot);
                            })(i);
                        }
                    };

                    var sync = function () {
                        activeIndex = Math.max(
                            0,
                            Math.min(maxIndex(), Math.round(viewport.scrollLeft / Math.max(step(), 1)))
                        );

                        if (dotsWrap) {
                            var dots = dotsWrap.children;

                            for (var i = 0; i < dots.length; i++) {
                                dots[i].setAttribute('aria-current', i === activeIndex ? 'true' : 'false');
                            }
                        }

                        if (prevButton) {
                            prevButton.disabled = activeIndex <= 0;
                        }

                        if (nextButton) {
                            nextButton.disabled = activeIndex >= maxIndex();
                        }
                    };

                    if (prevButton) {
                        prevButton.addEventListener('click', function () {
                            goTo(activeIndex - 1);
                        });
                    }

                    if (nextButton) {
                        nextButton.addEventListener('click', function () {
                            goTo(activeIndex + 1);
                        });
                    }

                    viewport.addEventListener('scroll', function () {
                        window.requestAnimationFrame(sync);
                    }, { passive: true });

                    var resizeTimer;
                    window.addEventListener('resize', function () {
                        window.clearTimeout(resizeTimer);
                        resizeTimer = window.setTimeout(function () {
                            buildDots();
                            sync();
                        }, 150);
                    });

                    /* Drag dengan mouse (touch sudah native scroll). Snap mati saat drag. */
                    var isPointerDown = false;
                    var dragStartX = 0;
                    var dragStartScroll = 0;
                    var dragMoved = false;
                    var suppressClickUntil = 0;

                    /* Cegah klik tak sengaja (lightbox terbuka) setelah geser. */
                    viewport.addEventListener('click', function (event) {
                        if (Date.now() < suppressClickUntil) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }, true);

                    var endDrag = function () {
                        if (!isPointerDown) {
                            return;
                        }

                        isPointerDown = false;
                        viewport.classList.remove('is-dragging');

                        if (dragMoved) {
                            suppressClickUntil = Date.now() + 120;

                            /* Kembalikan posisi ke titik snap terdekat. */
                            window.requestAnimationFrame(function () {
                                goTo(Math.round(viewport.scrollLeft / Math.max(step(), 1)));
                            });
                        }
                    };

                    viewport.addEventListener('pointerdown', function (event) {
                        if (event.pointerType !== 'mouse' || event.button !== 0) {
                            return;
                        }

                        isPointerDown = true;
                        dragMoved = false;
                        dragStartX = event.clientX;
                        dragStartScroll = viewport.scrollLeft;
                        viewport.classList.add('is-dragging');
                    });

                    window.addEventListener('pointermove', function (event) {
                        if (!isPointerDown) {
                            return;
                        }

                        var delta = event.clientX - dragStartX;

                        if (Math.abs(delta) > 6) {
                            dragMoved = true;
                        }

                        viewport.scrollLeft = dragStartScroll - delta;
                    });

                    window.addEventListener('pointerup', endDrag);
                    window.addEventListener('pointercancel', endDrag);

                    buildDots();
                    sync();
                }
            }

            /* ===== Lightbox foto (dengan navigasi antar foto) ===== */
            var lightbox = document.getElementById('docLightbox');
            var lightboxImage = lightbox ? lightbox.querySelector('[data-lightbox-image]') : null;
            var lightboxCaption = lightbox ? lightbox.querySelector('[data-lightbox-caption]') : null;
            var lightboxCounter = lightbox ? lightbox.querySelector('[data-lightbox-counter]') : null;
            var lightboxPrev = lightbox ? lightbox.querySelector('[data-lightbox-prev]') : null;
            var lightboxNext = lightbox ? lightbox.querySelector('[data-lightbox-next]') : null;
            var closeButton = lightbox ? lightbox.querySelector('[data-lightbox-close]') : null;
            var triggers = lightbox
                ? Array.prototype.slice.call(document.querySelectorAll('[data-lightbox-src]'))
                : [];
            var currentIndex = 0;
            var lastFocused = null;

            var renderLightbox = function () {
                var trigger = triggers[currentIndex];

                if (!trigger) {
                    return;
                }

                var caption = trigger.getAttribute('data-lightbox-caption') || '';

                if (lightboxImage) {
                    lightboxImage.src = trigger.getAttribute('data-lightbox-src');
                    lightboxImage.alt = caption || 'Dokumentasi kegiatan';
                }

                if (lightboxCaption) {
                    lightboxCaption.textContent = caption;
                }

                if (lightboxCounter) {
                    lightboxCounter.textContent = triggers.length > 1
                        ? 'Foto ' + (currentIndex + 1) + ' dari ' + triggers.length
                        : '';
                }
            };

            var openLightbox = function (index) {
                if (!lightbox || triggers[index] === undefined) {
                    return;
                }

                currentIndex = index;
                lastFocused = triggers[index];
                renderLightbox();

                document.body.style.overflow = 'hidden';
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');

                if (closeButton) {
                    closeButton.focus();
                }
            };

            var closeLightbox = function () {
                if (!lightbox) {
                    return;
                }

                lightbox.classList.remove('is-open');
                lightbox.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';

                if (lastFocused instanceof HTMLElement) {
                    lastFocused.focus();
                }
            };

            var stepLightbox = function (offset) {
                if (triggers.length < 2) {
                    return;
                }

                currentIndex = (currentIndex + offset + triggers.length) % triggers.length;
                renderLightbox();
            };

            if (lightbox) {
                triggers.forEach(function (trigger, index) {
                    trigger.addEventListener('click', function () {
                        openLightbox(index);
                    });
                });

                lightbox.addEventListener('click', function (event) {
                    if (event.target === lightbox || event.target.closest('[data-lightbox-close]')) {
                        closeLightbox();
                    }
                });

                if (lightboxPrev) {
                    lightboxPrev.addEventListener('click', function () {
                        stepLightbox(-1);
                    });
                }

                if (lightboxNext) {
                    lightboxNext.addEventListener('click', function () {
                        stepLightbox(1);
                    });
                }

                if (triggers.length < 2) {
                    [lightboxPrev, lightboxNext].forEach(function (button) {
                        if (button) {
                            button.hidden = true;
                        }
                    });
                }

                document.addEventListener('keydown', function (event) {
                    if (!lightbox.classList.contains('is-open')) {
                        return;
                    }

                    if (event.key === 'Escape') {
                        closeLightbox();

                        return;
                    }

                    if (event.key === 'ArrowLeft') {
                        event.preventDefault();
                        stepLightbox(-1);
                    }

                    if (event.key === 'ArrowRight') {
                        event.preventDefault();
                        stepLightbox(1);
                    }
                });
            }
        }());
    </script>

</body>
</html>
