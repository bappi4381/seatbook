<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Dashboard')"
            :subtitle="__('You are signed in. Use the navigation to continue.')"
        />
    </x-slot>

    <div class="seatbook-container">
        <x-card>
            <p class="text-slate-700">{{ __("You're logged in!") }}</p>
        </x-card>
    </div>
</x-app-layout>
