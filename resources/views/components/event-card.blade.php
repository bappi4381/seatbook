@props(['event'])

<a href="{{ route('events.show', $event) }}" {{ $attributes->merge(['class' => 'group block rounded-xl bg-white p-5 shadow-soft ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:ring-brand-300']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">
                {{ $event->starts_at->format('D, M j · H:i') }}
            </p>
            <h3 class="mt-2 font-display text-xl font-semibold text-slate-900 group-hover:text-brand-800">
                {{ $event->title }}
            </h3>
        </div>
        <x-badge status="published">{{ __('Published') }}</x-badge>
    </div>

    <p class="mt-3 text-sm text-slate-600">{{ $event->venue }}</p>

    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
        <span class="font-semibold text-slate-900">
            {{ __('From :price', ['price' => number_format((float) ($event->seats_min_price ?? 0), 2)]) }}
        </span>
        <span class="text-slate-500">
            {{ __(':count seats left', ['count' => $event->available_seats_count]) }}
        </span>
    </div>
</a>
