@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] font-medium leading-5 text-emerald-800']) }}>
        {{ $status }}
    </div>
@endif
