<x-guest-layout
    title="Atur Ulang Kata Sandi"
    heading="Kata sandi"
    accent="baru."
    description="Buat kata sandi baru yang kuat untuk mengamankan kembali akses ke portal petugas."
>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">
            <span class="h-1.5 w-1.5 rounded-full bg-[#c9a45c]"></span>
            Atur ulang akses
        </div>

        <h2 class="mt-3 text-[27px] font-extrabold leading-tight tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
            Kata sandi baru
        </h2>

        <p class="mt-2.5 text-[13px] leading-6 text-[#6b7873]">
            Masukkan email akun dan kata sandi baru Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Alamat email" />

            <x-text-input
                id="email"
                class="mt-2 block w-full"
                type="email"
                name="email"
                :value="old('email', $request->email)"
                required
                autofocus
                autocomplete="username"
                placeholder="nama@instansi.go.id"
            />

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi baru" />

            <x-text-input
                id="password"
                class="mt-2 block w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" />

            <x-text-input
                id="password_confirmation"
                class="mt-2 block w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
            />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3.5">
            Simpan kata sandi baru
        </x-primary-button>
    </form>

    <div class="mt-6 border-t border-[#eee8dc] pt-5 text-center">
        <a href="{{ route('login') }}" class="group inline-flex items-center gap-2 text-[12px] font-bold text-[#52605b] transition hover:text-[#0e3b31]">
            <svg class="h-3.5 w-3.5 transition group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 12H5m6 6-6-6 6-6"/>
            </svg>
            Kembali ke halaman masuk
        </a>
    </div>
</x-guest-layout>
