<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$event->title" />
    </x-slot>

    <div
        class="seatbook-container pb-28 lg:pb-10"
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
                <x-card class="space-y-6">
                    <div class="space-y-3 border-b border-slate-100 pb-5">
                        @if ($event->description)
                            <p class="text-slate-700">{{ $event->description }}</p>
                        @endif

                        <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                            <p class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" />
                                    <circle cx="12" cy="11" r="2.5" />
                                </svg>
                                <span class="font-medium text-slate-900">{{ $event->venue }}</span>
                            </p>
                            <p class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="16" rx="2" />
                                    <path stroke-linecap="round" d="M16 3v4M8 3v4M3 11h18" />
                                </svg>
                                <span class="font-medium text-slate-900">{{ $event->starts_at->format('D, M j Y · H:i') }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="pt-2" role="presentation">
                        <div class="seat-stage">STAGE</div>
                    </div>

                    <div class="mt-8 overflow-x-auto pb-2" role="group" aria-label="{{ __('Seat map') }}">
                        <div class="mx-auto min-w-max space-y-3 px-1">
                            <template x-for="row in rows()" :key="row.row">
                                <div class="flex items-center justify-center gap-3">
                                    <div class="w-6 shrink-0 text-center text-sm font-semibold text-slate-400" x-text="row.row"></div>

                                    <div class="flex items-center justify-center gap-2">
                                        <template x-for="seat in rowSegments(row.seats).left" :key="'L'+seat.id">
                                            <button
                                                type="button"
                                                :class="seatClass(seat)"
                                                :aria-label="seatAriaLabel(seat)"
                                                :aria-pressed="selectedIds.includes(seat.id).toString()"
                                                :aria-disabled="isUnavailable(seat).toString()"
                                                :tabindex="(canSelect(seat) || selectedIds.includes(seat.id)) ? 0 : -1"
                                                @click="toggleSeat(seat)"
                                                @keydown="onSeatKeydown($event, seat)"
                                            >
                                                <span x-text="seat.label"></span>
                                                <template x-if="visualState(seat) === 'selected'">
                                                    <svg class="seat-state-icon text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414L8.75 11.836l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </template>
                                                <template x-if="visualState(seat) === 'held_by_me' || visualState(seat) === 'held_by_others'">
                                                    <svg class="seat-state-icon text-amber-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <circle cx="12" cy="12" r="9" />
                                                        <path stroke-linecap="round" d="M12 7v5l3 2" />
                                                    </svg>
                                                </template>
                                                <template x-if="visualState(seat) === 'booked'">
                                                    <svg class="seat-state-icon text-slate-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2v-8a2 2 0 00-2-2zm-7-2a2 2 0 114 0v2h-4V7zm3 8a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                    </svg>
                                                </template>
                                            </button>
                                        </template>

                                        <div class="seat-aisle" x-show="rowSegments(row.seats).showAisle" aria-hidden="true"></div>

                                        <template x-for="seat in rowSegments(row.seats).right" :key="'R'+seat.id">
                                            <button
                                                type="button"
                                                :class="seatClass(seat)"
                                                :aria-label="seatAriaLabel(seat)"
                                                :aria-pressed="selectedIds.includes(seat.id).toString()"
                                                :aria-disabled="isUnavailable(seat).toString()"
                                                :tabindex="(canSelect(seat) || selectedIds.includes(seat.id)) ? 0 : -1"
                                                @click="toggleSeat(seat)"
                                                @keydown="onSeatKeydown($event, seat)"
                                            >
                                                <span x-text="seat.label"></span>
                                                <template x-if="visualState(seat) === 'selected'">
                                                    <svg class="seat-state-icon text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.414L8.75 11.836l6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </template>
                                                <template x-if="visualState(seat) === 'held_by_me' || visualState(seat) === 'held_by_others'">
                                                    <svg class="seat-state-icon text-amber-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <circle cx="12" cy="12" r="9" />
                                                        <path stroke-linecap="round" d="M12 7v5l3 2" />
                                                    </svg>
                                                </template>
                                                <template x-if="visualState(seat) === 'booked'">
                                                    <svg class="seat-state-icon text-slate-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2v-8a2 2 0 00-2-2zm-7-2a2 2 0 114 0v2h-4V7zm3 8a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                    </svg>
                                                </template>
                                            </button>
                                        </template>
                                    </div>

                                    <div class="hidden w-6 shrink-0 text-center text-sm font-semibold text-slate-400 lg:block" x-text="row.row"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-x-4 gap-y-3 border-t border-slate-100 pt-5 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-2">
                            <x-seat-swatch state="available" label="A" />
                            <span>{{ __('Available') }} <span class="font-semibold text-slate-800" x-text="countByState('available')"></span></span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-seat-swatch state="selected" label="A" />
                            <span>{{ __('Selected') }} <span class="font-semibold text-slate-800" x-text="countByState('selected')"></span></span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-seat-swatch state="held_by_me" label="A" />
                            <span>{{ __('Held by me') }} <span class="font-semibold text-slate-800" x-text="countByState('held_by_me')"></span></span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-seat-swatch state="held_by_others" label="A" />
                            <span>{{ __('Held by others') }} <span class="font-semibold text-slate-800" x-text="countByState('held_by_others')"></span></span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-seat-swatch state="booked" label="A" />
                            <span>{{ __('Booked') }} <span class="font-semibold text-slate-800" x-text="countByState('booked')"></span></span>
                        </span>
                    </div>
                </x-card>
            </div>

            <aside class="hidden lg:block lg:self-start">
                <div class="lg:sticky lg:top-24">
                    <x-card class="space-y-5">
                        <h2 class="font-display text-2xl font-semibold text-slate-900">{{ __('Summary') }}</h2>

                        <template x-if="error">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900" role="alert" x-text="error"></div>
                        </template>
                        <template x-if="message">
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900" role="status" x-text="message"></div>
                        </template>

                        @auth
                            @if (auth()->user()->isAttendee())
                                <div>
                                    <template x-if="selectedSeats().length === 0 && heldSeats().length === 0">
                                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                                            {{ __('Pick up to 6 seats') }}
                                        </div>
                                    </template>

                                    <template x-if="selectedSeats().length > 0">
                                        <div class="space-y-3">
                                            <p class="text-sm font-medium text-slate-500">{{ __('Selected seats') }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="seat in selectedSeats()" :key="'sel'+seat.id">
                                                    <span class="inline-flex items-center gap-1 rounded-xl bg-brand-50 px-2.5 py-1.5 text-sm font-semibold text-brand-800 ring-1 ring-brand-200">
                                                        <span x-text="seat.label"></span>
                                                        <span class="font-normal text-brand-700" x-text="money(seat.price)"></span>
                                                        <button
                                                            type="button"
                                                            class="rounded-md p-0.5 text-brand-700 hover:bg-brand-100"
                                                            @click="removeSelected(seat.id)"
                                                            :aria-label="'Remove seat ' + seat.label"
                                                        >
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </span>
                                                </template>
                                            </div>
                                            <p class="text-sm text-slate-600">
                                                {{ __('Total') }}:
                                                <span class="text-xl font-bold text-slate-900" x-text="money(selectedTotal())"></span>
                                            </p>
                                        </div>
                                    </template>
                                </div>

                                <x-primary-button type="button" class="w-full" @click="holdSelected()" x-bind:disabled="holding || selectedIds.length === 0">
                                    <span x-text="holding ? '{{ __('Holding…') }}' : '{{ __('Hold selected seats') }}'"></span>
                                </x-primary-button>

                                <div class="space-y-4 border-t border-slate-100 pt-4" x-show="heldSeats().length > 0 || holdExpired">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">{{ __('Your held seats') }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <template x-for="seat in heldSeats()" :key="'held'+seat.id">
                                                <span class="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-2.5 py-1.5 text-sm font-semibold text-amber-900 ring-1 ring-amber-200">
                                                    <span x-text="seat.label"></span>
                                                    <span class="font-normal" x-text="money(seat.price)"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <p class="mt-3 text-sm text-slate-600">
                                            {{ __('Hold total') }}:
                                            <span class="text-xl font-bold text-slate-900" x-text="money(heldTotal())"></span>
                                        </p>
                                    </div>

                                    <template x-if="holdExpiresAt && !holdExpired">
                                        <div class="inline-flex items-center rounded-full px-4 py-2 text-sm font-bold tabular-nums" :class="countdownClass()">
                                            {{ __('Time left') }}: <span class="ms-2" x-text="remainingLabel()"></span>
                                        </div>
                                    </template>

                                    <template x-if="holdExpired">
                                        <p class="text-sm font-semibold text-rose-700">{{ __('Your hold expired') }}</p>
                                    </template>

                                    <x-primary-button type="button" class="w-full" @click="confirmBooking()" x-bind:disabled="confirming || holdExpired || heldSeats().length === 0 || remainingSeconds() <= 0">
                                        <span x-text="confirming ? '{{ __('Confirming…') }}' : '{{ __('Confirm booking') }}'"></span>
                                    </x-primary-button>

                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-slate-600 underline-offset-2 hover:text-slate-900 hover:underline"
                                        @click="releaseHolds()"
                                        x-show="heldSeats().length > 0"
                                    >
                                        {{ __('Release seats') }}
                                    </button>
                                </div>
                            @else
                                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900" role="status">
                                    {{ __("Organizers can't book seats") }}
                                </div>
                            @endif
                        @else
                            <div class="space-y-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-4 text-sm text-brand-950">
                                <p>{{ __('Log in as an attendee to hold and confirm seats.') }}</p>
                                <x-ui-button variant="primary" :href="route('login')" type="button" class="w-full">{{ __('Log in to book') }}</x-ui-button>
                            </div>
                        @endauth
                    </x-card>
                </div>
            </aside>
        </div>

        {{-- Mobile sticky summary --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-500">
                        <span x-text="selectedSeats().length || heldSeats().length"></span>
                        {{ __('seats') }}
                    </p>
                    <p class="truncate text-sm font-semibold text-slate-900" x-text="money(selectedSeats().length ? selectedTotal() : heldTotal())"></p>
                </div>

                @auth
                    @if (auth()->user()->isAttendee())
                        <template x-if="primaryMobileAction() === 'confirm'">
                            <x-primary-button type="button" @click="confirmBooking()" x-bind:disabled="confirming || holdExpired || remainingSeconds() <= 0">
                                <span x-text="confirming ? '{{ __('Confirming…') }}' : '{{ __('Confirm') }}'"></span>
                            </x-primary-button>
                        </template>
                        <template x-if="primaryMobileAction() === 'hold'">
                            <x-primary-button type="button" @click="holdSelected()" x-bind:disabled="holding || selectedIds.length === 0">
                                <span x-text="holding ? '{{ __('Holding…') }}' : '{{ __('Hold') }}'"></span>
                            </x-primary-button>
                        </template>
                    @endif
                @else
                    <x-ui-button variant="primary" :href="route('login')" type="button">{{ __('Log in') }}</x-ui-button>
                @endauth
            </div>

            <template x-if="holdExpiresAt && !holdExpired">
                <div class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold tabular-nums" :class="countdownClass()">
                    {{ __('Time left') }}: <span class="ms-1" x-text="remainingLabel()"></span>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
