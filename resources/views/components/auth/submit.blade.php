{{-- Full-width submit button; shows a spinner and `busy` text once its form (with x-data="{ busy: false }" @submit="busy = true") is sent. --}}
@props(['busy' => null])

<x-primary-button {{ $attributes->class('w-full justify-center gap-2 py-3 disabled:opacity-70') }} x-bind:disabled="busy">
    <svg x-show="busy" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    <span x-text="busy ? @js($busy ?? __('Please wait…')) : @js(trim($slot))">{{ $slot }}</span>
</x-primary-button>
