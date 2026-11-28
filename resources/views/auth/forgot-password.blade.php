<x-guest-layout :title="__('Forgot Password')">
    <x-auth.header :eyebrow="__('Forgot Password')" :title="__('Reset your password')" :subtitle="__('Enter the email address of your account and we will send you a link to choose a new password.')" />

    <x-auth.status :status="session('status')" />
    <x-auth.errors />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <x-auth.input name="email" :label="__('Email')" icon="mail" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@company.com" />

        <x-auth.submit :busy="__('Sending…')">{{ __('Email Password Reset Link') }}</x-auth.submit>
    </form>

    <x-auth.back-link :href="route('login')">{{ __('Back to login') }}</x-auth.back-link>
</x-guest-layout>
