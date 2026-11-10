@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center rounded-xl bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-800'
    : 'inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
