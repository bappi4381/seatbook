@props(['state' => 'available', 'label' => null])

@php
    $classes = match ($state) {
        'selected' => 'seat-chair seat-chair-selected',
        'held_by_me' => 'seat-chair seat-chair-held-me',
        'held_by_others' => 'seat-chair seat-chair-held-others',
        'booked' => 'seat-chair seat-chair-booked',
        default => 'seat-chair seat-chair-available',
    };
@endphp

<span {{ $attributes->merge(['class' => 'relative '.$classes.' !h-7 !w-7 !text-[10px] sm:!h-8 sm:!w-8', 'aria-hidden' => 'true']) }}>
    <span>{{ $label ?? 'A1' }}</span>

    @if ($state === 'booked')
        <svg class="seat-state-icon text-slate-500" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2v-8a2 2 0 00-2-2zm-7-2a2 2 0 114 0v2h-4V7zm3 8a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
        </svg>
    @elseif ($state === 'held_by_others' || $state === 'held_by_me')
        <svg class="seat-state-icon {{ $state === 'held_by_me' ? 'text-amber-950' : 'text-amber-800' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" d="M12 7v5l3 2" />
        </svg>
    @elseif ($state === 'selected')
        <svg class="seat-state-icon text-white" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414L8.75 11.836l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
    @endif
</span>
