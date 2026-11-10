<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="__('Profile')"
            :subtitle="__('Manage your account details and security settings.')"
        />
    </x-slot>

    <div class="seatbook-container max-w-3xl space-y-6 pb-10">
        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card>
            @include('profile.partials.delete-user-form')
        </x-card>
    </div>
</x-app-layout>
