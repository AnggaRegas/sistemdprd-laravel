<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-[#d8ceba] bg-white px-5 py-3 text-sm font-bold text-[#33443e] shadow-sm transition hover:border-[#0e3b31]/30 hover:bg-[#fbf9f4] focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/20 disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
