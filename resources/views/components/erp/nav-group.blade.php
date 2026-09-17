@props(['label', 'active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="pt-1">
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium text-gray-400 uppercase tracking-wide text-xs hover:text-white"
    >
        <span>{{ $label }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-1 space-y-1">
        {{ $slot }}
    </div>
</div>
