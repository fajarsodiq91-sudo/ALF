<x-guest-layout :title="__('Reset Password')">
    <x-auth.header :eyebrow="__('Reset Password')" :title="__('Choose a new password')" :subtitle="__('Pick a password you have not used here before.')" />

    <x-auth.errors />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth.input name="email" :label="__('Email')" icon="mail" type="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />

        <x-auth.password :label="__('New Password')" required autocomplete="new-password" />

        <x-auth.password name="password_confirmation" :label="__('Confirm Password')" required autocomplete="new-password" />

        <x-auth.submit :busy="__('Saving…')">{{ __('Reset Password') }}</x-auth.submit>
    </form>

    <x-auth.back-link :href="route('login')">{{ __('Back to login') }}</x-auth.back-link>
</x-guest-layout>
