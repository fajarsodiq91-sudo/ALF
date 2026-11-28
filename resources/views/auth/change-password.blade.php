{{-- Shown instead of the profile page while an account still uses its initial password. --}}
<x-guest-layout :title="__('Set New Password')">
    <div class="mb-6 grid h-12 w-12 place-items-center rounded-xl bg-brand-50 text-brand">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
    </div>

    <x-auth.header
        :eyebrow="__('First Login')"
        :title="__('Set your new password')"
        :subtitle="__('Hi :name, before using the ERP please replace your initial password with one only you know.', ['name' => \Illuminate\Support\Str::of($user->name)->before(' ')])" />

    <div class="mb-5 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        <p>
            Please set a new password before using the ERP.
            @if ($user->employee)
                {{ __('Your current password is your employee number') }} (<strong>{{ $user->employee->employee_number }}</strong>).
            @endif
        </p>
    </div>

    <x-auth.errors bag="updatePassword" />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5"
          x-data="{ busy: false, pw: '', confirm: '' }" @submit="busy = true">
        @csrf
        @method('put')

        <x-auth.password id="update_password_current_password" name="current_password" bag="updatePassword" :label="__('Current Password')" required autofocus autocomplete="current-password" />

        <div>
            <x-auth.password id="update_password_password" bag="updatePassword" :label="__('New Password')" required autocomplete="new-password" x-model="pw" />

            {{-- Strength meter --}}
            <div class="mt-2" x-show="pw.length" x-cloak
                 x-data="{ get score() { let s = 0; if (pw.length >= 8) s++; if (pw.length >= 12) s++; if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++; if (/\d/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++; return Math.min(4, s); } }">
                <div class="flex gap-1">
                    <template x-for="i in 4" :key="i">
                        <span class="h-1 flex-1 rounded-full transition-colors duration-300"
                              :class="i <= score ? ['bg-red-400', 'bg-amber-400', 'bg-lime-500', 'bg-green-500'][score - 1] : 'bg-steel-200'"></span>
                    </template>
                </div>
                <p class="mt-1 text-xs text-steel-500" x-text="[@js(__('Too weak')), @js(__('Weak')), @js(__('Fair')), @js(__('Good')), @js(__('Strong'))][score]"></p>
            </div>
            <p class="mt-1.5 text-xs text-steel-500">{{ __('At least 8 characters. Mixing letters, numbers and symbols makes it stronger.') }}</p>
        </div>

        <div>
            <x-auth.password id="update_password_password_confirmation" name="password_confirmation" bag="updatePassword" :label="__('Confirm New Password')" required autocomplete="new-password" x-model="confirm" />
            <p x-show="confirm.length && confirm !== pw" x-cloak class="mt-1.5 text-xs font-medium text-red-600">{{ __('The passwords do not match yet.') }}</p>
            <p x-show="confirm.length && confirm === pw" x-cloak class="mt-1.5 text-xs font-medium text-green-600">{{ __('Passwords match.') }}</p>
        </div>

        <x-auth.submit :busy="__('Saving…')">{{ __('Save and continue') }}</x-auth.submit>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-steel-500 hover:text-brand transition-colors">{{ __('Not you? Log out') }}</button>
    </form>
</x-guest-layout>
