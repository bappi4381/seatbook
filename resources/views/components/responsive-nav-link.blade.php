@props(['active'])

@php
$classes = ($active ?? false)
    ? 'block w-full rounded-xl bg-brand-50 px-3 py-2 text-base font-semibold text-brand-800'
    : 'block w-full rounded-xl px-3 py-2 text-base font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
