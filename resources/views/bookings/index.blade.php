<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Bookings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($bookings->isEmpty())
                        <p class="text-gray-600">{{ __('You have no bookings yet.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600">
                                        <th class="py-2 pr-4">{{ __('Event') }}</th>
                                        <th class="py-2 pr-4">{{ __('Seats') }}</th>
                                        <th class="py-2 pr-4">{{ __('Total') }}</th>
                                        <th class="py-2 pr-4">{{ __('Status') }}</th>
                                        <th class="py-2">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($bookings as $booking)
                                        <tr>
                                            <td class="py-3 pr-4">
                                                <div class="font-medium">{{ $booking->event->title }}</div>
                                                <div class="text-gray-500">{{ $booking->event->starts_at->format('Y-m-d H:i') }}</div>
                                            </td>
                                            <td class="py-3 pr-4">{{ $booking->seats->pluck('label')->join(', ') }}</td>
                                            <td class="py-3 pr-4">{{ number_format((float) $booking->total_amount, 2) }}</td>
                                            <td class="py-3 pr-4">
                                                <span @class([
                                                    'inline-flex px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                                    'bg-green-100 text-green-800' => $booking->status === 'confirmed',
                                                    'bg-gray-100 text-gray-700' => $booking->status === 'cancelled',
                                                ])>
                                                    {{ $booking->status }}
                                                </span>
                                            </td>
                                            <td class="py-3">
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('bookings.show', $booking) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('View') }}</a>
                                                    @can('cancel', $booking)
                                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Cancel') }}</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
