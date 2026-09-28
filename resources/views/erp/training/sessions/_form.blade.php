@php $session ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="training_program_id" class="block text-sm font-medium text-gray-700">Program</label>
        <select name="training_program_id" id="training_program_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">Select program</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((int) old('training_program_id', $session->training_program_id ?? '') === $program->id)>{{ $program->name }}{{ $program->is_active ? '' : ' (inactive)' }}</option>
            @endforeach
        </select>
        @error('training_program_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer</label>
        <select name="customer_id" id="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Public batch / no customer —</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $session->customer_id ?? '') === $customer->id)>{{ $customer->customer_code }} — {{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $session?->start_date?->format('Y-m-d')) }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $session?->end_date?->format('Y-m-d')) }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="delivery_mode" class="block text-sm font-medium text-gray-700">Delivery Mode</label>
        <select name="delivery_mode" id="delivery_mode" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Services\MasterData::options('delivery_mode', $session->delivery_mode ?? null) as $value => $label)
                <option value="{{ $value }}" @selected(old('delivery_mode', $session->delivery_mode ?? 'onsite') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('delivery_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
        <input type="text" name="location" id="location" value="{{ old('location', $session->location ?? '') }}"  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="instructor_id" class="block text-sm font-medium text-gray-700">Instructor</label>
        <select name="instructor_id" id="instructor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Not assigned —</option>
            @foreach ($instructors as $instructor)
                <option value="{{ $instructor->id }}" @selected((int) old('instructor_id', $session->instructor_id ?? '') === $instructor->id)>{{ $instructor->name }}</option>
            @endforeach
        </select>
        @error('instructor_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\TrainingSession::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $session->status ?? 'planned') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="participants_count" class="block text-sm font-medium text-gray-700">Participants</label>
        <input type="number" name="participants_count" id="participants_count" value="{{ old('participants_count', $session->participants_count ?? 0) }}" required min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('participants_count') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="fee" class="block text-sm font-medium text-gray-700">Fee (Rp)</label>
        <input type="number" name="fee" id="fee" value="{{ old('fee', $session->fee ?? 0) }}" required step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('fee') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="materials_url" class="block text-sm font-medium text-gray-700">Learning Materials Link</label>
        <input type="url" name="materials_url" id="materials_url" value="{{ old('materials_url', $session->materials_url ?? '') }}" placeholder="https://" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Shown to the customer in their portal once filled in.</p>
        @error('materials_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="certificate_url" class="block text-sm font-medium text-gray-700">Certificate Link</label>
        <input type="url" name="certificate_url" id="certificate_url" value="{{ old('certificate_url', $session->certificate_url ?? '') }}" placeholder="https://" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Shown to the customer in their portal once filled in.</p>
        @error('certificate_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('notes', $session->notes ?? '') }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">{{ $session ? 'Update Session' : 'Create Session' }}</button>
    <a href="{{ route('training.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
