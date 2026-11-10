<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Create event')"
            :subtitle="__('Set the details and generate a seat map in one step.')"
        />
    </x-slot>

    <div class="seatbook-container max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('organizer.events.store') }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                @include('organizer.events._form')

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6">
                    <x-primary-button x-bind:disabled="submitting">
                        <span x-text="submitting ? '{{ __('Creating…') }}' : '{{ __('Create event') }}'"></span>
                    </x-primary-button>
                    <x-ui-button variant="ghost" :href="route('organizer.events.index')" type="button">{{ __('Cancel') }}</x-ui-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
