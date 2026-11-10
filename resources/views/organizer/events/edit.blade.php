<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Edit event')"
            :subtitle="__('Update publishing details without changing the seat layout.')"
        />
    </x-slot>

    <div class="seatbook-container max-w-3xl space-y-6">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-card :label="__('Total seats')" :value="$event->seats_count" />
            <x-stat-card :label="__('Booked')" :value="$event->booked_seats_count" />
            <x-stat-card :label="__('Available')" :value="$event->available_seats_count" />
        </div>

        <x-card>
            <form method="POST" action="{{ route('organizer.events.update', $event) }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')

                @include('organizer.events._form', ['event' => $event->loadMissing('seats')])

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6">
                    <x-primary-button x-bind:disabled="submitting">
                        <span x-text="submitting ? '{{ __('Saving…') }}' : '{{ __('Save changes') }}'"></span>
                    </x-primary-button>
                    <x-ui-button variant="ghost" :href="route('organizer.events.index')" type="button">{{ __('Cancel') }}</x-ui-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <h3 class="font-display text-xl font-semibold text-slate-900">{{ __('Bookings') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Read-only list of tickets sold for this event.') }}</p>

            @if ($event->bookings->isEmpty())
                <div class="mt-6">
                    <x-empty-state
                        :title="__('No bookings yet')"
                        :description="__('Confirmed attendee bookings will appear here.')"
                    />
                </div>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="py-2 pr-4 font-medium">{{ __('Attendee') }}</th>
                                <th class="py-2 pr-4 font-medium">{{ __('Seats') }}</th>
                                <th class="py-2 pr-4 font-medium">{{ __('Total') }}</th>
                                <th class="py-2 font-medium">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($event->bookings as $booking)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="font-medium text-slate-900">{{ $booking->user->name }}</div>
                                        <div class="text-slate-500">{{ $booking->user->email }}</div>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-700">{{ $booking->seats->pluck('label')->join(', ') }}</td>
                                    <td class="py-3 pr-4 font-medium text-slate-900">{{ number_format((float) $booking->total_amount, 2) }}</td>
                                    <td class="py-3">
                                        <x-badge :status="$booking->status">{{ $booking->status }}</x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
