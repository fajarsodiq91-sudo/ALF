<x-guest-layout :title="__('Register')">
    <x-auth.header :eyebrow="__('Register')" :title="__('Create an account')" />

    <x-auth.errors />

    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <x-auth.input name="name" :label="__('Name')" icon="name" type="text" :value="old('name')" required autofocus autocomplete="name" />

        <x-auth.input name="email" :label="__('Email')" icon="mail" type="email" :value="old('email')" required autocomplete="username" />

        <x-auth.password :label="__('Password')" required autocomplete="new-password" />

        <x-auth.password name="password_confirmation" :label="__('Confirm Password')" required autocomplete="new-password" />

        <x-auth.submit :busy="__('Creating account…')">{{ __('Register') }}</x-auth.submit>
    </form>

    <x-auth.back-link :href="route('login')">{{ __('Already registered? Log in') }}</x-auth.back-link>
</x-guest-layout>
