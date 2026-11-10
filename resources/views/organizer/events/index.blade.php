<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Events') }}
            </h2>
            <a href="{{ route('organizer.events.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Create Event') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($events->isEmpty())
                        <p class="text-gray-600">{{ __('You have not created any events yet.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">{{ __('Title') }}</th>
                                        <th class="py-2 pr-4">{{ __('Venue') }}</th>
                                        <th class="py-2 pr-4">{{ __('Starts') }}</th>
                                        <th class="py-2 pr-4">{{ __('Status') }}</th>
                                        <th class="py-2 pr-4">{{ __('Seats') }}</th>
                                        <th class="py-2 pr-4">{{ __('Booked') }}</th>
                                        <th class="py-2">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($events as $event)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium">{{ $event->title }}</td>
                                            <td class="py-3 pr-4">{{ $event->venue }}</td>
                                            <td class="py-3 pr-4">{{ $event->starts_at->format('Y-m-d H:i') }}</td>
                                            <td class="py-3 pr-4 capitalize">{{ $event->status }}</td>
                                            <td class="py-3 pr-4">{{ $event->seats_count }}</td>
                                            <td class="py-3 pr-4">{{ $event->booked_seats_count }}</td>
                                            <td class="py-3">
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('organizer.events.edit', $event) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                                                    <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $events->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
