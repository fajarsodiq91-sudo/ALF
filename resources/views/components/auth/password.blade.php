{{-- Password input with a lock icon, show/hide toggle and a Caps Lock warning. Optional `aside` slot sits right of the label. --}}
@props(['name' => 'password', 'label', 'aside' => null])

@php($id = $attributes->get('id', $name))

<div x-data="{ show: false, caps: false }">
    <div class="flex items-center justify-between">
        <x-input-label :for="$id" :value="$label" />
        {{ $aside }}
    </div>
    <div class="relative mt-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-steel-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <x-text-input :id="$id" :name="$name" type="password" x-bind:type="show ? 'text' : 'password'"
                      @keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                      @keydown="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
                      {{ $attributes->except('id')->class(['block w-full pl-10 pr-10 py-2.5 focus:border-brand focus:ring-brand', 'border-red-300' => $errors->has($name)]) }} />
        <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-steel-400 hover:text-steel-600 focus:outline-none focus:ring-2 focus:ring-brand"
                :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
            <svg x-show="!show" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12C3.73 7.94 7.52 5 12 5c4.48 0 8.27 2.94 9.54 7-1.27 4.06-5.06 7-9.54 7-4.48 0-8.27-2.94-9.54-7z"/></svg>
            <svg x-show="show" x-cloak class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.88 18.82A10.05 10.05 0 0112 19c-4.48 0-8.27-2.94-9.54-7a9.97 9.97 0 012.27-3.6m3.1-2.3A9.95 9.95 0 0112 5c4.48 0 8.27 2.94 9.54 7a10.03 10.03 0 01-4.13 5.4M15 12a3 3 0 00-4.24-2.74M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18"/></svg>
        </button>
    </div>
    <p x-show="caps" x-cloak x-transition.opacity class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-amber-600">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.26 3.1c.77-1.36 2.71-1.36 3.48 0l5.58 9.92c.75 1.33-.21 2.98-1.74 2.98H4.42c-1.53 0-2.49-1.65-1.74-2.98L8.26 3.1zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        {{ __('Caps Lock is on') }}
    </p>
</div>
