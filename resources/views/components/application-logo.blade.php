@props(['showWordmark' => true])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-sm font-bold text-white shadow-soft" aria-hidden="true">
        SB
    </span>
    @if ($showWordmark)
        <span class="font-display text-xl font-semibold tracking-tight text-slate-900">SEATBOOK</span>
    @endif
</span>
