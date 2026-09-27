@props(['label', 'active' => false, 'icon' => null])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="pt-0.5">
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center gap-2.5 px-2.5 py-[7px] rounded-md text-[13px] font-medium text-steel-300 hover:bg-white/5 hover:text-white"
    >
        @if ($icon)
            <x-erp.nav-icon :name="$icon" />
        @endif

        <span class="flex-1 text-left truncate">{{ $label }}</span>

        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
        {{ $slot }}
    </div>
</div>
