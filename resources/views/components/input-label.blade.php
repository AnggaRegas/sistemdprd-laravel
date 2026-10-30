@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[12px] font-bold text-[#33443e]']) }}>
    {{ $value ?? $slot }}
</label>
