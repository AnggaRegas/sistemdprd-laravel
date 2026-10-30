@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'block w-full rounded-xl border border-[#e0d7c4] bg-[#fbfaf6] px-4 py-3 text-sm text-[#123b32] placeholder:text-[#a5ada9] shadow-sm transition focus:border-[#c9a45c] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#c9a45c]/15']) !!}>
