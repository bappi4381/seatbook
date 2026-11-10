<x-app-layout>
    <div class="relative overflow-hidden border-b border-slate-200 bg-slate-950 text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,_rgba(45,212,191,0.28),_transparent_35%),radial-gradient(circle_at_80%_0%,_rgba(56,189,248,0.2),_transparent_30%),linear-gradient(180deg,_rgba(15,23,42,0.2),_rgba(15,23,42,0.85))]"></div>
        <div class="seatbook-container relative grid gap-10 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
            <div>
                <p class="font-display text-5xl font-semibold tracking-tight sm:text-6xl">SEATBOOK</p>
                <h1 class="mt-4 max-w-xl text-2xl font-medium text-slate-100 sm:text-3xl">
                    {{ __('Find your seat before the lights go down.') }}
                </h1>
                <p class="mt-4 max-w-lg text-base text-slate-300">
                    {{ __('Browse live events, hold seats for 10 minutes, and confirm your booking with confidence.') }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-ui-button variant="primary" :href="route('events.index')" type="button">{{ __('Browse events') }}</x-ui-button>
                    @guest
                        <x-ui-button variant="secondary" :href="route('register')" type="button">{{ __('Create account') }}</x-ui-button>
                    @endguest
                </div>
            </div>

            <div class="rounded-xl bg-white/10 p-6 shadow-soft ring-1 ring-white/15 backdrop-blur">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-200">{{ __('How it works') }}</p>
                <ol class="mt-4 space-y-4 text-sm text-slate-100">
                    <li><span class="font-semibold text-white">1.</span> {{ __('Pick an upcoming event and open the seat map.') }}</li>
                    <li><span class="font-semibold text-white">2.</span> {{ __('Hold up to 6 seats for 10 minutes.') }}</li>
                    <li><span class="font-semibold text-white">3.</span> {{ __('Confirm before the timer ends — seats are yours.') }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="seatbook-container py-12">
        <x-page-header
            :title="__('Upcoming events')"
            :subtitle="__('Fresh listings with seats still available. Choose a show and reserve your place.')"
        >
            <x-slot name="actions">
                <x-ui-button variant="secondary" :href="route('events.index')" type="button">{{ __('View all') }}</x-ui-button>
            </x-slot>
        </x-page-header>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($events as $event)
                <x-event-card :event="$event" />
            @empty
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state
                        :title="__('No upcoming events yet')"
                        :description="__('Organizers can publish events with seat maps. Check back soon.')"
                    >
                        <x-slot name="actions">
                            <x-ui-button variant="primary" :href="route('events.index')" type="button">{{ __('Browse events') }}</x-ui-button>
                        </x-slot>
                    </x-empty-state>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
