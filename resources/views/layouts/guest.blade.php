@props([
    'title' => 'Portal Petugas',
    'heading' => 'Akses terbatas',
    'accent' => 'untuk petugas.',
    'description' => 'Kelola arsip, aspirasi, dan informasi DPRD Kota Parepare dalam satu ruang kerja yang aman dan terintegrasi.',
])

<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0e3b31">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title }} | DPRD Kota Parepare</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --green: #0e3b31;
            --green-dark: #082a23;
            --gold: #c9a45c;
            --gold-light: #e6c47d;
            --cream: #f7f4ec;
            --line: #e7dfd0;
            --ink: #123b32;
            --muted: #6b7873;
        }

        body {
            background: var(--cream);
            font-family: 'Plus Jakarta Sans', 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        ::selection {
            background: var(--gold-light);
            color: var(--green);
        }

        .auth-grid {
            background-image:
                linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .85), rgba(0, 0, 0, .3));
        }

        .auth-rise {
            animation: auth-rise .6s cubic-bezier(.22, .61, .36, 1) both;
        }

        @keyframes auth-rise {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-rise {
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

<body class="min-h-screen antialiased text-slate-800">
    <main class="grid min-h-screen lg:grid-cols-[1.02fr_.98fr]">

        <!-- BRAND PANEL -->
        <section class="relative hidden overflow-hidden bg-[#0e3b31] px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-16 xl:py-14">
            <div class="auth-grid absolute inset-0 opacity-80"></div>
            <div class="absolute -right-32 -top-24 h-96 w-96 rounded-full bg-[#c9a45c]/20 blur-[100px]"></div>
            <div class="absolute -bottom-28 -left-20 h-80 w-80 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="absolute right-10 top-1/3 hidden h-72 w-72 rounded-full border border-white/[0.06] xl:block"></div>

            <div class="relative flex items-center justify-between gap-4">
                <a href="/" class="group flex items-center gap-3" aria-label="DPRD Kota Parepare - Beranda">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-[#e6c47d] ring-1 ring-white/10 transition group-hover:bg-white/15">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M3 21h18M5 21V8l7-4 7 4v13M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-3h4v3"/>
                        </svg>
                    </span>

                    <span class="leading-tight">
                        <span class="block text-sm font-extrabold tracking-[.04em]">DPRD KOTA PAREPARE</span>
                        <span class="mt-1 block text-[10px] font-bold uppercase tracking-[.18em] text-[#e6c47d]/70">Portal Aspirasi &amp; Informasi</span>
                    </span>
                </a>

                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.06] px-3 py-1.5 text-[9px] font-extrabold uppercase tracking-[.16em] text-white/55">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                    Portal Petugas
                </span>
            </div>

            <div class="relative max-w-[500px] py-12">
                <p class="flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.2em] text-[#e6c47d]">
                    <span class="h-px w-8 bg-[#c9a45c]"></span>
                    Sistem Pengarsipan &amp; Aspirasi
                </p>

                <h1 class="mt-5 text-[44px] font-extrabold leading-[1.06] tracking-[-0.04em] xl:text-[52px]">
                    {{ $heading }}<br>
                    <span class="text-[#e6c47d]">{{ $accent }}</span>
                </h1>

                <p class="mt-5 max-w-md text-sm leading-7 text-white/60">
                    {{ $description }}
                </p>

                <div class="mt-10 space-y-3">
                    <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-[#e6c47d]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>
                            </svg>
                        </span>
                        <span>
                            <strong class="block text-[12px] font-bold">Arsip terpadu</strong>
                            <span class="mt-0.5 block text-[10px] leading-5 text-white/45">Dokumen resmi tersimpan rapi dan mudah ditelusuri.</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-[#e6c47d]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <span>
                            <strong class="block text-[12px] font-bold">Akses terproteksi</strong>
                            <span class="mt-0.5 block text-[10px] leading-5 text-white/45">Setiap sesi diverifikasi untuk menjaga keamanan data.</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/[0.06] text-[#e6c47d]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3.5 2"/>
                            </svg>
                        </span>
                        <span>
                            <strong class="block text-[12px] font-bold">Jejak aktivitas</strong>
                            <span class="mt-0.5 block text-[10px] leading-5 text-white/45">Penggunaan sistem tercatat untuk kebutuhan audit.</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="relative flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6 text-[10px] font-medium text-white/30">
                <p>&copy; {{ date('Y') }} DPRD Kota Parepare</p>
                <p>Sekretariat DPRD &middot; Portal internal</p>
            </div>
        </section>

        <!-- FORM PANEL -->
        <section class="relative flex min-h-screen flex-col bg-[#f7f4ec]">
            <div class="flex items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:justify-end">
                <a href="/" class="flex items-center gap-2.5 lg:hidden" aria-label="DPRD Kota Parepare - Beranda">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0e3b31] text-[#e6c47d]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M3 21h18M5 21V8l7-4 7 4v13M8 11h2m4 0h2M8 15h2m4 0h2M10 21v-3h4v3"/>
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block text-[10px] font-extrabold tracking-[.05em] text-[#0e3b31]">DPRD KOTA PAREPARE</span>
                        <span class="mt-0.5 block text-[8px] font-bold uppercase tracking-[.16em] text-[#a17f3e]">Portal Petugas</span>
                    </span>
                </a>

                <a href="/" class="group inline-flex items-center gap-2 text-[11px] font-bold text-[#52605b] transition hover:text-[#0e3b31]">
                    <svg class="h-3.5 w-3.5 transition group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M19 12H5m6 6-6-6 6-6"/>
                    </svg>
                    <span class="hidden sm:inline">Kembali ke beranda</span>
                    <span class="sm:hidden">Beranda</span>
                </a>
            </div>

            <div class="flex flex-1 items-center justify-center px-5 py-8 sm:px-8 sm:py-10">
                <div class="w-full max-w-[440px]">
                    <div class="auth-rise rounded-[28px] border border-[#e7dfd0] bg-white p-6 shadow-[0_35px_90px_-60px_rgba(14,59,49,.65)] sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-5 text-center text-[9px] font-bold uppercase tracking-[.16em] text-[#a0957e]">
                        Sekretariat DPRD Kota Parepare &middot; Portal internal
                    </p>
                </div>
            </div>

            <div class="px-5 pb-6 text-center text-[10px] text-[#a0957e] sm:px-8">
                &copy; {{ date('Y') }} DPRD Kota Parepare. Hak cipta dilindungi.
            </div>
        </section>

    </main>
</body>
</html>
