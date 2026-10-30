<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e3b31] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#082a23] focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/25 disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
