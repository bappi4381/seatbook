@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white shadow-soft ring-1 ring-slate-200/70'.($padding ? ' p-6' : '')]) }}>
    {{ $slot }}
</div>
