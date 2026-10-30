<x-guest-layout
    title="Ganti Kata Sandi Wajib"
    heading="Amankan akun"
    accent="Anda."
    description="Kata sandi awal atau hasil reset administrator wajib diganti sebelum Anda dapat melanjutkan ke portal internal."
>
    <div class="mb-7">
        <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#a17f3e]">
            <span class="h-1.5 w-1.5 rounded-full bg-[#c9a45c]"></span>
            Keamanan akun
        </div>

        <h2 class="mt-3 text-[27px] font-extrabold leading-tight tracking-[-0.03em] text-[#123b32] sm:text-[30px]">
            Ganti kata sandi wajib
        </h2>

        <p class="mt-2.5 text-[13px] leading-6 text-[#6b7873]">
            Demi keamanan, ganti kata sandi awal Anda sebelum melanjutkan. Kata sandi baru minimal 8 karakter dan
            tidak boleh sama dengan kata sandi sementara.
        </p>
    </div>

    @if ($errors->forcePassword->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[11px] font-medium text-red-700" role="alert">
            <strong class="block text-[12px] font-bold">Kata sandi belum dapat diperbarui.</strong>
            <ul class="mt-1 list-inside list-disc space-y-1">
                @foreach ($errors->forcePassword->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="current_password" class="mb-2 block text-[12px] font-bold text-[#33443e]">
                Kata sandi saat ini
            </label>
            <input
                id="current_password"
                name="current_password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan kata sandi sementara"
                class="block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] px-4 py-3.5 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15 @if ($errors->forcePassword->has('current_password')) !border-red-300 !bg-red-50/40 @endif"
            />
            @if ($errors->forcePassword->has('current_password'))
                <p class="mt-2 text-[11px] font-medium text-red-600">{{ $errors->forcePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="password" class="mb-2 block text-[12px] font-bold text-[#33443e]">
                Kata sandi baru
            </label>
            <input
                id="password"
                name="password"
                type="password"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
                class="block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] px-4 py-3.5 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15 @if ($errors->forcePassword->has('password')) !border-red-300 !bg-red-50/40 @endif"
            />
            @if ($errors->forcePassword->has('password'))
                <p class="mt-2 text-[11px] font-medium text-red-600">{{ $errors->forcePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-[12px] font-bold text-[#33443e]">
                Ulangi kata sandi baru
            </label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                class="block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] px-4 py-3.5 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15"
            />
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-4 py-3.5 text-sm font-bold text-white shadow-[0_18px_35px_-22px_rgba(14,59,49,.9)] transition hover:bg-[#082a23] focus:outline-none focus:ring-4 focus:ring-[#0e3b31]/20"
        >
            Simpan kata sandi baru
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M5 12h14m-6-6 6 6-6 6"/>
            </svg>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-[11px] font-bold text-[#6b7873] underline decoration-[#c9a45c]/50 underline-offset-4 transition hover:text-[#0e3b31]">
            Keluar dari akun ini
        </button>
    </form>
</x-guest-layout>
