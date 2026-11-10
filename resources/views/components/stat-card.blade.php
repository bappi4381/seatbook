@props(['label', 'value', 'hint' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-5 shadow-soft ring-1 ring-slate-200/70']) }}>
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl font-semibold text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
