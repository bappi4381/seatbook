<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-3xl font-semibold text-slate-900">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('Log in to hold seats or manage your events.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded-md border-slate-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-700 hover:text-brand-800" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full" x-bind:disabled="submitting">
            <span x-text="submitting ? '{{ __('Signing in…') }}' : '{{ __('Log in') }}'"></span>
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        {{ __('No account yet?') }}
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ __('Register') }}</a>
    </p>
</x-guest-layout>
