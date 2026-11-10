<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$event->title" :subtitle="$event->venue.' · '.$event->starts_at->format('D, M j Y · H:i')" />
    </x-slot>

    <div
        class="seatbook-container"
        x-data="seatMap({
            seats: @js($seatsJson),
            holdExpiresAt: @js($myHoldExpiresAt),
            serverNow: @js($serverNow),
            isAttendee: @js(auth()->check() && auth()->user()->isAttendee()),
            holdUrl: @js(route('events.holds.store', $event)),
            seatsUrl: @js(route('events.seats', $event)),
            bookingUrl: @js(route('events.bookings.store', $event)),
            loginUrl: @js(route('login')),
            csrf: @js(csrf_token()),
            maxSeats: 6
        })"
    >
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <x-card class="space-y-5">
                    @if ($event->description)
                        <p class="text-slate-700">{{ $event->description }}</p>
                    @endif

                    <div class="rounded-xl bg-slate-900 py-3 text-center text-sm font-semibold tracking-[0.35em] text-white" role="presentation">
                        STAGE
                    </div>

                    <div class="space-y-3 overflow-x-auto pb-2" role="group" aria-label="{{ __('Seat map') }}">
                        <template x-for="row in rows()" :key="row.row">
                            <div class="flex items-center gap-2">
                                <div class="w-6 shrink-0 text-sm font-semibold text-slate-500" x-text="row.row"></div>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="seat in row.seats" :key="seat.id">
                                        <button
                                            type="button"
                                            :class="seatClass(seat)"
                                            :aria-label="seatAriaLabel(seat)"
                                            :aria-pressed="selectedIds.includes(seat.id).toString()"
                                            :tabindex="(canSelect(seat) || selectedIds.includes(seat.id)) ? 0 : -1"
                                            @click="toggleSeat(seat)"
                                            @keydown="onSeatKeydown($event, seat)"
                                            x-text="seat.label"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-3 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-emerald-50 ring-1 ring-emerald-200"></span> {{ __('Available') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-brand-700"></span> {{ __('Selected') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-400"></span> {{ __('Held by me') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-orange-100 ring-1 ring-orange-200"></span> {{ __('Held by others') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-400"></span> {{ __('Booked') }}</span>
                    </div>
                </x-card>
            </div>

            <div class="lg:sticky lg:top-6 lg:self-start">
                <x-card class="space-y-4">
                    <h2 class="font-display text-2xl font-semibold text-slate-900">{{ __('Summary') }}</h2>

                    <template x-if="error">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900" role="alert" x-text="error"></div>
                    </template>
                    <template x-if="message">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900" role="status" x-text="message"></div>
                    </template>

                    <div>
                        <p class="text-sm text-slate-500">{{ __('Selected seats') }}</p>
                        <p class="mt-1 font-medium text-slate-900" x-text="selectedSeats().map(s => s.label).join(', ') || '—'"></p>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ __('Total') }}:
                            <span class="font-semibold text-slate-900" x-text="selectedTotal().toFixed(2)"></span>
                        </p>
                    </div>

                    @auth
                        @if (auth()->user()->isAttendee())
                            <x-primary-button type="button" class="w-full" @click="holdSelected()" x-bind:disabled="holding || selectedIds.length === 0">
                                <span x-text="holding ? '{{ __('Holding…') }}' : '{{ __('Hold selected seats') }}'"></span>
                            </x-primary-button>

                            <div class="space-y-3 border-t border-slate-100 pt-4" x-show="heldSeats().length > 0 || holdExpired">
                                <div>
                                    <p class="text-sm text-slate-500">{{ __('Your held seats') }}</p>
                                    <p class="mt-1 font-medium text-slate-900" x-text="heldSeats().map(s => s.label).join(', ') || '—'"></p>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ __('Hold total') }}:
                                        <span class="font-semibold" x-text="heldTotal().toFixed(2)"></span>
                                    </p>
                                </div>

                                <template x-if="holdExpiresAt && !holdExpired">
                                    <p class="text-sm font-semibold" :class="countdownClass()">
                                        {{ __('Time left') }}: <span x-text="remainingLabel()"></span>
                                    </p>
                                </template>

                                <template x-if="holdExpired">
                                    <p class="text-sm font-semibold text-rose-700">{{ __('Your hold expired') }}</p>
                                </template>

                                <x-primary-button type="button" class="w-full" @click="confirmBooking()" x-bind:disabled="confirming || holdExpired || heldSeats().length === 0 || remainingSeconds() <= 0">
                                    <span x-text="confirming ? '{{ __('Confirming…') }}' : '{{ __('Confirm booking') }}'"></span>
                                </x-primary-button>

                                <x-secondary-button type="button" class="w-full" @click="releaseHolds()" x-show="heldSeats().length > 0">
                                    {{ __('Release holds') }}
                                </x-secondary-button>
                            </div>
                        @else
                            <p class="text-sm text-slate-600">{{ __('Only attendees can hold and book seats.') }}</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-600">
                            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('Log in') }}</a>
                            {{ __('as an attendee to hold seats.') }}
                        </p>
                    @endauth
                </x-card>
            </div>
        </div>

        {{-- Mobile sticky summary strip --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Selected') }}</p>
                    <p class="text-sm font-semibold" x-text="selectedSeats().map(s => s.label).join(', ') || heldSeats().map(s => s.label).join(', ') || '—'"></p>
                </div>
                <p class="text-sm font-semibold" x-text="(selectedSeats().length ? selectedTotal() : heldTotal()).toFixed(2)"></p>
            </div>
        </div>
    </div>
</x-app-layout>
