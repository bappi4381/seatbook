<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('My bookings')"
            :subtitle="__('Review confirmed tickets and cancel before the event starts.')"
        />
    </x-slot>

    <div class="seatbook-container">
        @if ($bookings->isEmpty())
            <x-empty-state
                :title="__('No bookings yet')"
                :description="__('Browse upcoming events and hold seats to get started.')"
            >
                <x-slot name="actions">
                    <x-ui-button variant="primary" :href="route('events.index')" type="button">{{ __('Browse events') }}</x-ui-button>
                </x-slot>
            </x-empty-state>
        @else
            <div class="grid gap-4">
                @foreach ($bookings as $booking)
                    <x-card class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-display text-xl font-semibold text-slate-900">{{ $booking->event->title }}</h2>
                                <x-badge :status="$booking->status">{{ $booking->status }}</x-badge>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $booking->event->starts_at->format('D, M j Y · H:i') }} · {{ $booking->event->venue }}</p>
                            <p class="mt-3 text-sm text-slate-700">
                                <span class="font-medium">{{ __('Seats') }}:</span>
                                {{ $booking->seats->pluck('label')->join(', ') }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format((float) $booking->total_amount, 2) }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui-button variant="secondary" :href="route('bookings.show', $booking)" type="button">{{ __('View') }}</x-ui-button>
                            @can('cancel', $booking)
                                <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('{{ __('Cancel this booking?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button type="submit">{{ __('Cancel') }}</x-danger-button>
                                </form>
                            @endcan
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
