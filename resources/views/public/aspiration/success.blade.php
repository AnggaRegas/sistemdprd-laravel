@extends('layouts.public')

@section('title', 'Aspirasi Berhasil Dikirim | DPRD Kota Parepare')
@section('meta_description', 'Konfirmasi pengiriman aspirasi masyarakat kepada DPRD Kota Parepare.')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl px-5 pb-28 pt-14 text-center sm:px-8 sm:pt-20">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#e6c47d] text-[#123b32] shadow-[0_18px_40px_-18px_rgba(230,196,125,.6)]">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </div>

            <p class="mt-6 text-[10px] font-extrabold uppercase tracking-[.22em] text-[#e6c47d]">Konfirmasi pengiriman</p>

            <h1 class="mt-3 text-3xl font-extrabold tracking-[-0.035em] sm:text-[42px]">
                Aspirasi Berhasil Dikirim!
            </h1>

            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                Terima kasih telah menyampaikan aspirasi Anda. Aspirasi berhasil masuk ke sistem dan akan melalui proses verifikasi.
            </p>
        </div>
    </section>

    <section class="relative z-10 mx-auto -mt-16 max-w-3xl px-5 pb-24 sm:px-8">
        <div class="rounded-3xl border border-[#e7dfd0] bg-white p-6 text-center shadow-[0_28px_80px_-45px_rgba(14,59,49,.5)] sm:p-10">
            <h2 class="text-xl font-extrabold text-[#123b32] sm:text-2xl">Aspirasi Anda sudah kami terima</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[#63716c]">
                Aspirasi akan ditinjau oleh petugas DPRD. Bila diperlukan, petugas akan menghubungi Anda melalui kontak yang dicantumkan.
            </p>

            <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('welcome') }}" class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-[#0e3b31] px-5 py-3 text-[13px] font-extrabold text-white transition hover:bg-[#082a23] sm:w-auto">
                    Kembali ke Portal Publik
                    <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6"/>
                    </svg>
                </a>
            </div>

            <div class="mt-7 flex items-start gap-3 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] p-4 text-left">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#a17f3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 11v5m0-8h.01"/>
                </svg>
                <p class="text-[11px] leading-6 text-[#63716c]">
                    Administrator akan meninjau laporan terlebih dahulu. Jika diperlukan, petugas dapat menghubungi Anda melalui kontak yang telah dicantumkan.
                </p>
            </div>
        </div>
    </section>
@endsection
