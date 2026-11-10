<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-3xl font-semibold text-slate-900">{{ __('Create your account') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('Choose how you will use SEATBOOK.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ submitting: false, role: '{{ old('role', 'attendee') }}' }" @submit="submitting = true">
        @csrf

        <fieldset>
            <legend class="text-sm font-medium text-slate-700">{{ __('Register as') }}</legend>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="cursor-pointer rounded-xl border p-4 transition" :class="role === 'attendee' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="role" value="attendee" class="sr-only" x-model="role" @checked(old('role', 'attendee') === 'attendee')>
                    <span class="block text-sm font-semibold text-slate-900">{{ __('Attendee') }}</span>
                    <span class="mt-1 block text-xs text-slate-600">{{ __('Browse events, hold seats, and book.') }}</span>
                </label>

                <label class="cursor-pointer rounded-xl border p-4 transition" :class="role === 'organizer' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="role" value="organizer" class="sr-only" x-model="role" @checked(old('role') === 'organizer')>
                    <span class="block text-sm font-semibold text-slate-900">{{ __('Organizer') }}</span>
                    <span class="mt-1 block text-xs text-slate-600">{{ __('Create events and manage seat layouts.') }}</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </fieldset>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full" x-bind:disabled="submitting">
            <span x-text="submitting ? '{{ __('Creating account…') }}' : '{{ __('Register') }}'"></span>
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
