@props(['type' => 'status'])

@php
    $message = $type === 'error' ? session('error') : session('status');
    $classes = $type === 'error'
        ? 'border-rose-200 bg-rose-50 text-rose-900'
        : 'border-emerald-200 bg-emerald-50 text-emerald-900';
@endphp

@if ($message)
    <div
        {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm {$classes}", 'role' => 'status']) }}
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition
    >
        <div class="flex items-start justify-between gap-3">
            <p>{{ $message }}</p>
            <button type="button" class="rounded-lg p-1 text-current/70 hover:bg-black/5" @click="show = false" aria-label="{{ __('Dismiss') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    </div>
@endif
