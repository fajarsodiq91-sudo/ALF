<x-participant-shell title="Welcome">
    <div class="px-6 py-8 text-center">
        <h1 class="text-lg font-semibold text-gray-800">You have joined {{ $participant['program'] }}</h1>
        <p class="mt-2 text-sm text-gray-500">Welcome, {{ $participant['name'] }}. Write down your login details:</p>
        <dl class="mx-auto mt-4 max-w-xs space-y-2 rounded-md bg-brand-50 px-4 py-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-600">Customer ID</dt><dd class="font-mono font-semibold text-gray-900">{{ $participant['code'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-600">First password</dt><dd class="font-mono font-semibold text-gray-900">{{ $participant['code'] }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-gray-500">You will be asked to choose a new password the first time you log in.</p>
        <a href="{{ route('portal.login') }}" class="mt-5 inline-flex rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm">Go to the portal</a>
    </div>
</x-participant-shell>
