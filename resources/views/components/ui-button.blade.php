@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
    'loading' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-brand-700 text-white hover:bg-brand-800 shadow-soft',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100',
        'link' => 'bg-transparent px-0 py-0 text-brand-700 hover:text-brand-800',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'disabled' => $loading || $attributes->get('disabled')]) }}>
        <span @if ($loading) class="opacity-0" @endif>{{ $slot }}</span>
        @if ($loading)
            <span class="absolute">{{ __('Please wait…') }}</span>
        @endif
    </button>
@endif
