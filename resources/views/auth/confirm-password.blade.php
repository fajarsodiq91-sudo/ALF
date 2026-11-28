<x-guest-layout :title="__('Confirm Password')">
    <x-auth.header :eyebrow="__('Secure Area')" :title="__('Confirm your password')" :subtitle="__('This is a secure area of the application. Please confirm your password before continuing.')" />

    <x-auth.errors />

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <x-auth.password :label="__('Password')" required autofocus autocomplete="current-password" />

        <x-auth.submit :busy="__('Checking…')">{{ __('Confirm') }}</x-auth.submit>
    </form>
</x-guest-layout>
