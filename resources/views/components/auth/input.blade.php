{{-- Text input with a leading icon: user | mail | name. --}}
@props(['name', 'label', 'icon' => 'user'])

@php
    $paths = [
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'mail' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'name' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
    ];
@endphp

<div>
    <x-input-label :for="$attributes->get('id', $name)" :value="$label" />
    <div class="relative mt-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-steel-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$icon] ?? $paths['user'] }}"/></svg>
        <x-text-input :id="$attributes->get('id', $name)" :name="$name" {{ $attributes->except('id')->class(['block w-full pl-10 py-2.5 focus:border-brand focus:ring-brand', 'border-red-300' => $errors->has($name)]) }} />
    </div>
</div>
