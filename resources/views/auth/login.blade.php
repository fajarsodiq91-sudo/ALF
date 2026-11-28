<x-guest-layout>
    <x-auth.header :eyebrow="__('Staff Login')" :title="__('Sign in to the ERP')" :subtitle="__('Use your work email or employee number.')" />

    <x-auth.status :status="session('status')" />
    <x-auth.errors />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <x-auth.input name="email" :label="__('Email or Employee Number')" icon="user" type="text" :value="old('email')" required autofocus autocomplete="username" placeholder="name@company.com" />

        <x-auth.password :label="__('Password')" required autocomplete="current-password">
            @if (Route::has('password.request'))
                <x-slot:aside>
                    <a class="text-xs font-medium text-steel-500 hover:text-brand transition-colors" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                </x-slot:aside>
            @endif
        </x-auth.password>

        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-steel-300 text-brand shadow-sm focus:ring-brand" name="remember">
            <span class="ms-2 text-sm text-steel-600">{{ __('Remember me') }}</span>
        </label>

        <x-auth.submit :busy="__('Signing in…')">{{ __('Log in') }}</x-auth.submit>
    </form>

    <div class="mt-8 rounded-lg border border-steel-200 bg-white/70 px-4 py-3 text-sm text-steel-600">
        {{ __('Customer or participant?') }}
        <a href="{{ route('portal.login') }}" class="font-semibold text-brand hover:text-brand-dark">{{ __('Go to the Customer Portal') }} &rarr;</a>
    </div>
</x-guest-layout>
