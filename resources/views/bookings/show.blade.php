<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Booking details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Event') }}</p>
                    <p class="font-semibold text-lg">{{ $booking->event->title }}</p>
                    <p class="text-sm text-gray-600">{{ $booking->event->venue }} · {{ $booking->event->starts_at->format('D, M j Y H:i') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('Status') }}</p>
                    <p class="capitalize font-medium">{{ $booking->status }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('Seats') }}</p>
                    <p class="font-medium">{{ $booking->seats->pluck('label')->join(', ') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">{{ __('Total amount') }}</p>
                    <p class="font-semibold text-lg">{{ number_format((float) $booking->total_amount, 2) }}</p>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <a href="{{ route('bookings.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Back to my bookings') }}</a>

                    @can('cancel', $booking)
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Cancel booking') }}</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
