<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Booking confirmed')"
            :subtitle="__('Your seats are reserved for this event.')"
        />
    </x-slot>

    <div class="seatbook-container max-w-3xl">
        <x-card class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display text-2xl font-semibold text-slate-900">{{ $booking->event->title }}</h2>
                <x-badge :status="$booking->status">{{ $booking->status }}</x-badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-slate-500">{{ __('Venue') }}</p>
                    <p class="font-medium text-slate-900">{{ $booking->event->venue }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">{{ __('Starts') }}</p>
                    <p class="font-medium text-slate-900">{{ $booking->event->starts_at->format('D, M j Y · H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">{{ __('Seats') }}</p>
                    <p class="font-medium text-slate-900">{{ $booking->seats->pluck('label')->join(', ') }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">{{ __('Total amount') }}</p>
                    <p class="font-display text-2xl font-semibold text-slate-900">{{ number_format((float) $booking->total_amount, 2) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                <x-ui-button variant="secondary" :href="route('bookings.index')" type="button">{{ __('Back to my bookings') }}</x-ui-button>

                @can('cancel', $booking)
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('{{ __('Cancel this booking?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">{{ __('Cancel booking') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        </x-card>
    </div>
</x-app-layout>
