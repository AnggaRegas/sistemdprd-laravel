<x-guest-layout
    title="Verifikasi Email"
    heading="Verifikasi"
    accent="email Anda."
    description="Satu langkah lagi untuk mengaktifkan akun dan mengamankan akses ke portal petugas."
>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">
            <span class="h-1.5 w-1.5 rounded-full bg-[#c9a45c]"></span>
            Verifikasi akun
        </div>

        <h2 class="mt-3 text-[27px] font-extrabold leading-tight tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
            Verifikasi email Anda
        </h2>

        <p class="mt-2.5 text-[13px] leading-6 text-[#6b7873]">
            Kami telah mengirimkan tautan verifikasi ke email yang Anda daftarkan. Klik tautan tersebut untuk mengaktifkan akun.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] font-medium leading-5 text-emerald-800">
            Tautan verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button class="w-full py-3.5">
                Kirim ulang email verifikasi
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-secondary-button type="submit" class="w-full">
                Keluar dari akun
            </x-secondary-button>
        </form>
    </div>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-[#eee8dc] bg-[#fbf9f4] px-4 py-3.5 text-[11px] leading-5 text-[#75827d]">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#a17f3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path d="M4 4h16v16H4z" opacity=".25"/>
            <path d="m3 7 9 6 9-6"/>
            <path d="M3 5h18v14H3z"/>
        </svg>
        <p>Tidak menerima email? Periksa folder spam atau kirim ulang tautan verifikasi.</p>
    </div>
</x-guest-layout>
