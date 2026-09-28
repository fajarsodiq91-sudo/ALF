@php $program ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Program Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $program->name ?? '') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="program_type" class="block text-sm font-medium text-gray-700">Program Type</label>
        <select name="program_type" id="program_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Services\MasterData::options('program_type', $program->program_type ?? null) as $value => $label)
                <option value="{{ $value }}" @selected(old('program_type', $program->program_type ?? 'learning') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('program_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="session_minutes" class="block text-sm font-medium text-gray-700">Session length (minutes)</label>
        <input type="number" name="session_minutes" id="session_minutes" value="{{ old('session_minutes', $program->session_minutes ?? '') }}" min="15" max="720" step="5" placeholder="e.g. 60" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Customers choose any start time inside the operating hours; the end time follows from this length. Leave empty to book the whole operating-hours slot as it is.</p>
        @error('session_minutes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="duration_days" class="block text-sm font-medium text-gray-700">Number of meetings</label>
        <input type="number" name="duration_days" id="duration_days" value="{{ old('duration_days', $program->duration_days ?? 1) }}" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Customers pick a date and time slot for exactly this many meetings when they register.</p>
        @error('duration_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="standard_price" class="block text-sm font-medium text-gray-700">Standard Price (Rp)</label>
        <input type="number" name="standard_price" id="standard_price" value="{{ old('standard_price', $program->standard_price ?? 0) }}" required step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('standard_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $program->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $program->is_active ?? true)) class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">{{ $program ? 'Update Program' : 'Create Program' }}</button>
    <a href="{{ route('training.programs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
