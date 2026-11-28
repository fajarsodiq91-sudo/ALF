<x-guest-layout :title="__('Verify Email')">
    <div class="mb-6 grid h-12 w-12 place-items-center rounded-xl bg-brand-50 text-brand">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
    </div>

    <x-auth.header :eyebrow="__('Verify Email')" :title="__('Check your inbox')" :subtitle="__('Before getting started, please verify your email address by clicking the link we just emailed to you. If you did not receive it, we will gladly send another.')" />

    @if (session('status') == 'verification-link-sent')
        <x-auth.status :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <form method="POST" action="{{ route('verification.send') }}" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <x-auth.submit :busy="__('Sending…')">{{ __('Resend Verification Email') }}</x-auth.submit>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-steel-500 hover:text-brand transition-colors">{{ __('Log Out') }}</button>
    </form>
</x-guest-layout>
