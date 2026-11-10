@props(['status' => 'default'])

@php
    $classes = match ($status) {
        'confirmed', 'published', 'available', 'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'cancelled', 'draft', 'danger', 'booked' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'held', 'warning' => 'bg-amber-50 text-amber-900 ring-amber-200',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
