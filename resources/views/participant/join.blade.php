<x-participant-shell title="Join training">
    @php $inputClass = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm'; @endphp
    <div class="px-6 py-6">
        <h1 class="text-lg font-semibold text-gray-800">Join {{ $session->program->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if ($session->customer) Invited by {{ $session->customer->name }}. @endif
            Fill in your details to get your own portal login.
        </p>

        @if (! $open)
            <p class="mt-4 rounded-md bg-amber-50 px-3 py-3 text-sm text-amber-800">Registration for this session is closed or already full. Please contact the person who invited you.</p>
        @else
            <form action="{{ route('participant.store', $session->participant_token) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 {{ $inputClass }}">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 {{ $inputClass }}">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="mt-1 {{ $inputClass }}">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Join</button>
            </form>
        @endif
    </div>
</x-participant-shell>
