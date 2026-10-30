<x-guest-layout
    title="Masuk Portal Petugas"
    heading="Selamat datang"
    accent="kembali."
    description="Kelola arsip, aspirasi, dan informasi DPRD Kota Parepare dalam satu ruang kerja yang aman dan terintegrasi."
>
    <div class="mb-7">
        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">
            <span class="h-1.5 w-1.5 rounded-full bg-[#c9a45c]"></span>
            Area terbatas
        </div>

        <h2 class="mt-3 text-[27px] font-extrabold leading-tight tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
            Masuk ke akun Anda
        </h2>

        <p class="mt-2.5 text-[13px] leading-6 text-[#6b7873]">
            Gunakan username atau alamat email yang telah terdaftar beserta kata sandi untuk melanjutkan.
        </p>
    </div>

    <x-auth-session-status :status="session('status')" class="mb-5" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- USERNAME -->
        <div>
            <label for="username" class="mb-2 block text-[12px] font-bold text-[#33443e]">
                Username atau Email
            </label>

            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#9aa39f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M5 21a7 7 0 0 1 14 0"/>
                    </svg>
                </span>

                <input
                    id="username"
                    name="username"
                    type="text"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan username atau email"
                    class="block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] py-3.5 pl-10 pr-4 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15 {{ $errors->has('username') ? '!border-red-300 !bg-red-50/40 focus:!border-red-400 focus:!ring-red-100' : '' }}"
                />
            </div>

            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- PASSWORD -->
        <div x-data="{ showPassword: false }">
            <label for="password" class="mb-2 block text-[12px] font-bold text-[#33443e]">
                Kata sandi
            </label>

            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#9aa39f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="10" rx="2"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                    </svg>
                </span>

                <input
                    id="password"
                    name="password"
                    type="password"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] py-3.5 pl-10 pr-11 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15 {{ $errors->has('password') ? '!border-red-300 !bg-red-50/40 focus:!border-red-400 focus:!ring-red-100' : '' }}"
                />

                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#9aa39f] transition hover:text-[#0e3b31] focus:outline-none focus-visible:text-[#0e3b31]"
                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                >
                    <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                    <svg x-show="showPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m3 3 18 18"/>
                        <path d="M10.6 5.2A9.8 9.8 0 0 1 12 5c6.5 0 10 7 10 7a17.4 17.4 0 0 1-3.2 4.2M6.6 6.6A17.1 17.1 0 0 0 2 12s3.5 7 10 7a9.9 9.9 0 0 0 4.2-.9"/>
                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- REMEMBER & FORGOT -->
        <div class="flex items-center justify-between gap-4">
            <label class="flex cursor-pointer items-center gap-2.5 text-[12px] font-medium text-[#63716c]">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    {{ old('remember') ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-[#cfc4ad] text-[#0e3b31] shadow-sm focus:ring-[#c9a45c]"
                >
                Ingat saya
            </label>

            <a href="{{ route('password.request') }}" class="text-[12px] font-bold text-[#a17f3e] transition hover:text-[#0e3b31]">
                Lupa kata sandi?
            </a>
        </div>

        <!-- SUBMIT -->
        <button
            type="submit"
            class="group flex w-full items-center justify-center gap-2.5 rounded-xl bg-[#0e3b31] px-5 py-3.5 text-sm font-extrabold text-white shadow-[0_18px_35px_-20px_rgba(14,59,49,.8)] transition hover:bg-[#082a23] focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/25"
        >
            Masuk ke sistem
            <svg class="h-4 w-4 text-[#e6c47d] transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="M5 12h14m-6-6 6 6-6 6"/>
            </svg>
        </button>
    </form>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-[#eee8dc] bg-[#fbf9f4] px-4 py-3.5 text-[11px] leading-5 text-[#75827d]">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#a17f3e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>
        <p>Akses hanya untuk petugas yang berwenang. Akun internal dibuat dan dikelola oleh administrator sekretariat, bukan melalui pendaftaran publik.</p>
    </div>
</x-guest-layout>
