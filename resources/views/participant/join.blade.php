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
            <form action="{{ route('participant.store', $session->participant_token) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
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
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone / WhatsApp</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="mt-1 {{ $inputClass }}">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" class="mt-1 {{ $inputClass }}">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" id="address" rows="3" class="mt-1 {{ $inputClass }}">{{ old('address') }}</textarea>
                    @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700">Photo (optional)</label>
                    <input type="file" name="photo" id="photo" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand">
                    @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @if ($session->program->terms)
                    <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-xs font-semibold text-amber-800">Terms &amp; Conditions</p>
                        <p class="mt-1 whitespace-pre-line text-xs text-amber-700">{{ $session->program->terms }}</p>
                    </div>
                @endif
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted')) @if ($session->program->terms) required @endif class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>I agree to the terms &amp; conditions of this program.</span>
                </label>
                @error('terms_accepted') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Join</button>
            </form>
        @endif
    </div>
</x-participant-shell>
