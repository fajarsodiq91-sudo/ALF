@props(['label', 'active' => false, 'icon' => null])

{{-- Accordion: `openGroup` lives on the sidebar <nav>, so opening one group closes the others.
     While the sidebar is collapsed, clicking a group expands the sidebar and opens it. --}}
<div x-data="{ id: @js($label) }" @if ($active) x-init="openGroup = id" @endif class="pt-0.5">
    <button
        @click="if (collapsed && window.innerWidth >= 1024) { collapsed = false; openGroup = id } else { openGroup = openGroup === id ? null : id }"
        type="button"
        @class([
            'relative w-full flex items-center gap-2.5 px-[13px] py-2 rounded-lg text-[13px] font-medium transition-colors duration-200 hover:bg-white/5 hover:text-white',
            'text-white bg-white/[0.07]' => $active,
            'text-steel-300' => ! $active,
        ])
    >
        @if ($icon)
            <x-erp.nav-icon :name="$icon" />
        @endif

        <span class="sb-label flex-1 text-left truncate">{{ $label }}</span>

        <svg xmlns="http://www.w3.org/2000/svg" class="sb-label h-3.5 w-3.5 shrink-0 transition-transform duration-300" :class="openGroup === id ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        <span class="sb-tip">{{ $label }}</span>
    </button>

    <div
        x-show="openGroup === id"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="sb-children mt-0.5 space-y-0.5"
    >
        {{ $slot }}
    </div>
</div>
