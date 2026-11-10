<nav x-data="{ open: false }" class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="seatbook-container">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="rounded-xl focus-visible:ring-2 focus-visible:ring-brand-500">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                        {{ __('Browse Events') }}
                    </x-nav-link>

                    @auth
                        @if (Auth::user()->isOrganizer())
                            <x-nav-link :href="route('organizer.events.index')" :active="request()->routeIs('organizer.events.*')">
                                {{ __('My Events') }}
                            </x-nav-link>
                        @endif

                        @if (Auth::user()->isAttendee())
                            <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                                {{ __('My Bookings') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="{{ __('Account menu') }}">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <x-ui-button variant="ghost" :href="route('login')" type="button">{{ __('Log in') }}</x-ui-button>
                    <x-ui-button variant="primary" :href="route('register')" type="button">{{ __('Register') }}</x-ui-button>
                @endauth
            </div>

            <div class="md:hidden">
                <button
                    type="button"
                    @click="open = ! open"
                    class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    :aria-expanded="open.toString()"
                    aria-label="{{ __('Toggle navigation') }}"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 md:hidden">
        <div class="space-y-1 px-4 py-3">
            <x-responsive-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                {{ __('Browse Events') }}
            </x-responsive-nav-link>

            @auth
                @if (Auth::user()->isOrganizer())
                    <x-responsive-nav-link :href="route('organizer.events.index')" :active="request()->routeIs('organizer.events.*')">
                        {{ __('My Events') }}
                    </x-responsive-nav-link>
                @endif

                @if (Auth::user()->isAttendee())
                    <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')">
                        {{ __('My Bookings') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <div class="border-t border-slate-200 px-4 py-3">
            @auth
                <div class="mb-3">
                    <div class="font-medium text-slate-900">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
                </div>
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            @else
                <div class="flex flex-col gap-2">
                    <x-ui-button variant="secondary" :href="route('login')" type="button">{{ __('Log in') }}</x-ui-button>
                    <x-ui-button variant="primary" :href="route('register')" type="button">{{ __('Register') }}</x-ui-button>
                </div>
            @endauth
        </div>
    </div>
</nav>
