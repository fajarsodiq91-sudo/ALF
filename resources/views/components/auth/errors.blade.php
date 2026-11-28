{{-- Every validation error in one box above the form. `bag` picks a named error bag. --}}
@props(['bag' => 'default'])

@php($messages = $errors->getBag($bag))
@if ($messages->any())
    <div {{ $attributes->class('mb-5 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700') }} role="alert">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-4a1 1 0 00-1 1v3a1 1 0 102 0V7a1 1 0 00-1-1zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <div class="space-y-0.5">
            @foreach ($messages->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    </div>
@endif
