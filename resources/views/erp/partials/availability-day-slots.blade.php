{{-- The slots of one day (Alpine `day` in scope), with their bookings. Used by availability-calendar. --}}
<template x-for="w in day.windows" :key="w.value">
    <div>
        <button type="button" @click="block(day, w)" :disabled="!blockable(day, w)"
                :title="w.state === 'booked' ? 'Fully booked' : (w.state === 'partial' ? 'Partly booked' : 'Available') + (blockable(day, w) ? ': click to block a time' : '')"
                class="block w-full rounded px-1 py-0.5 text-left text-[11px] leading-tight ring-1"
                :class="{
                    'bg-green-100 text-green-800 ring-green-300': w.state === 'available',
                    'bg-amber-100 text-amber-800 ring-amber-300': w.state === 'partial',
                    'bg-red-100 text-red-700 ring-red-300': w.state === 'booked',
                    'cursor-pointer hover:ring-2': blockable(day, w),
                    'cursor-default': !blockable(day, w),
                }"
                x-text="w.label"></button>
        <template x-for="b in w.bookings" :key="b.key">
            <button type="button" @click="b.blockId ? unblock(day, b) : view(day, b)"
                    :disabled="!(manage && b.blockId && !day.past) && !(b.detail && b.detail.length)"
                    :title="tooltipFor(b)"
                    class="mt-0.5 block w-full rounded px-1 text-left text-[10px] leading-tight"
                    :class="b.blockId ? 'bg-slate-200 text-slate-700 cursor-pointer hover:bg-slate-300' : (b.detail && b.detail.length ? 'bg-red-50 text-red-600 cursor-pointer hover:bg-red-100' : 'bg-red-50 text-red-600 cursor-default')"
                    x-text="b.start + '–' + b.end + (b.blockId ? ' blocked' : ' booked')"></button>
        </template>
    </div>
</template>
