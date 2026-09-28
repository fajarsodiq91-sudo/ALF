@php $project ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="">
        <label for="code" class="block text-sm font-medium text-gray-700">Project Code</label>
        <input type="text" name="code" id="code" value="{{ old('code', $project->code ?? $suggestedCode ?? '') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="name" class="block text-sm font-medium text-gray-700">Project Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $project->name ?? '') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer</label>
        <select name="customer_id" id="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— No customer / internal —</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $project->customer_id ?? '') === $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="project_manager_id" class="block text-sm font-medium text-gray-700">Project Manager</label>
        <select name="project_manager_id" id="project_manager_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">— Not assigned —</option>
            @foreach ($managers as $manager)
                <option value="{{ $manager->id }}" @selected((int) old('project_manager_id', $project->project_manager_id ?? '') === $manager->id)>{{ $manager->name }}</option>
            @endforeach
        </select>
        @error('project_manager_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $project?->start_date?->format('Y-m-d')) }}"  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="end_date" class="block text-sm font-medium text-gray-700">Deadline</label>
        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $project?->end_date?->format('Y-m-d')) }}"  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="contract_value" class="block text-sm font-medium text-gray-700">Contract Value (Rp)</label>
        <input type="number" name="contract_value" id="contract_value" value="{{ old('contract_value', $project->contract_value ?? 0) }}" required step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('contract_value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Models\Project::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $project->status ?? 'planned') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $project->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark">{{ $project ? 'Update Project' : 'Create Project' }}</button>
    <a href="{{ $project ? route('projects.show', $project) : route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
