<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('My events')"
            :subtitle="__('Create shows, track seat availability, and review bookings.')"
        >
            <x-slot name="actions">
                <x-ui-button variant="primary" :href="route('organizer.events.create')" type="button">{{ __('Create event') }}</x-ui-button>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="seatbook-container space-y-6">
        @if ($events->isEmpty())
            <x-empty-state
                :title="__('No events yet')"
                :description="__('Create your first event with a seat layout and ticket price.')"
            >
                <x-slot name="actions">
                    <x-ui-button variant="primary" :href="route('organizer.events.create')" type="button">{{ __('Create event') }}</x-ui-button>
                </x-slot>
            </x-empty-state>
        @else
            <div class="grid gap-4">
                @foreach ($events as $event)
                    <x-card class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-display text-xl font-semibold text-slate-900">{{ $event->title }}</h2>
                                    <x-badge :status="$event->status">{{ $event->status }}</x-badge>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ $event->venue }} · {{ $event->starts_at->format('Y-m-d H:i') }}</p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-ui-button variant="secondary" :href="route('organizer.events.edit', $event)" type="button">{{ __('Edit') }}</x-ui-button>
                                <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" onsubmit="return confirm('{{ __('Delete this event?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                                </form>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <x-stat-card :label="__('Seats')" :value="$event->seats_count" />
                            <x-stat-card :label="__('Booked')" :value="$event->booked_seats_count" />
                            <x-stat-card :label="__('Available')" :value="$event->available_seats_count" />
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div>
                {{ $events->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
