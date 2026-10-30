<x-guest-layout
    title="Akses Ditolak"
    heading="Akses"
    accent="ditolak."
    description="Halaman ini membutuhkan hak akses yang tidak tersedia pada akun Anda. Hubungi administrator sekretariat jika Anda merasa ini sebuah kekeliruan."
>
    <div class="mb-7">
        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">
            <span class="h-1.5 w-1.5 rounded-full bg-[#c9a45c]"></span>
            Kode 403
        </div>

        <h2 class="mt-3 text-[27px] font-extrabold leading-tight tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
            Anda tidak memiliki akses
        </h2>

        <p class="mt-2.5 text-[13px] leading-6 text-[#6b7873]">
            Permintaan Anda sudah diterima, tetapi role akun ini tidak diizinkan membuka halaman tersebut.
        </p>
    </div>

    <div class="rounded-2xl border border-[#eadfcd] bg-[#fbf7ee] px-5 py-4 text-[12px] leading-6 text-[#6b5a3d]">
        <strong class="block text-[12px] font-extrabold text-[#7d5f27]">Yang dapat Anda lakukan</strong>
        Kembali ke dashboard sesuai role Anda, atau masuk dengan akun yang memiliki hak akses yang sesuai.
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        @auth
            <a href="{{ route(auth()->user()->dashboardRouteName()) }}"
               class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[12px] font-extrabold text-white transition hover:bg-[#082a23]">
                <i class="ri-dashboard-3-line" aria-hidden="true"></i>
                Kembali ke dashboard
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-[12px] font-extrabold text-white transition hover:bg-[#082a23]">
                <i class="ri-login-box-line" aria-hidden="true"></i>
                Masuk ke portal
            </a>
        @endauth

        <a href="/"
           class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-[#d8cdbb] bg-white px-5 py-3 text-[12px] font-extrabold text-[#52605b] transition hover:border-[#c9a45c] hover:text-[#0e3b31]">
            <i class="ri-home-4-line" aria-hidden="true"></i>
            Beranda
        </a>
    </div>
</x-guest-layout>
