<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Browse events')"
            :subtitle="__('Search published upcoming shows by title or venue.')"
        />
    </x-slot>

    <div class="seatbook-container space-y-6">
        <x-card>
            <form method="GET" action="{{ route('events.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <label class="sr-only" for="q">{{ __('Search events') }}</label>
                <x-text-input id="q" name="q" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search by title or venue') }}" />
                <x-primary-button>{{ __('Search') }}</x-primary-button>
            </form>
        </x-card>

        @if ($events->isEmpty())
            <x-empty-state
                :title="__('No events match your search')"
                :description="__('Try a different keyword, or check back when organizers publish new shows.')"
            />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>

            <div>
                {{ $events->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
