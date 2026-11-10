<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Event') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('organizer.events.update', $event) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    @include('organizer.events._form', ['event' => $event->loadMissing('seats')])

                    <p class="text-sm text-gray-600">{{ __('Total seats: :count (layout is fixed after creation).', ['count' => $event->seats_count]) }}</p>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                        <a href="{{ route('organizer.events.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
