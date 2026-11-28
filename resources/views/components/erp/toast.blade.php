{{-- One-off toast for session('toast'): a string, or ['message' => ..., 'type' => 'success'|'error'|'info']. Slides in top-right and hides itself. --}}
@php
    $toast = session('toast');
    $toast = is_array($toast) ? $toast + ['type' => 'success'] : ($toast ? ['message' => $toast, 'type' => 'success'] : null);
    $styles = [
        'success' => ['ring-green-200', 'bg-green-100 text-green-600', 'M5 13l4 4L19 7', 'bg-green-500'],
        'error' => ['ring-red-200', 'bg-red-100 text-red-600', 'M6 18L18 6M6 6l12 12', 'bg-red-500'],
        'info' => ['ring-steel-200', 'bg-steel-100 text-steel-600', 'M13 16h-1v-4h-1m1-4h.01', 'bg-steel-500'],
    ];
@endphp

@if ($toast)
    @php([$ring, $badge, $icon, $bar] = $styles[$toast['type']] ?? $styles['success'])
    <div x-data="{ open: false }"
         x-init="$nextTick(() => open = true); setTimeout(() => open = false, 5000)"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 translate-x-8"
         x-cloak
         role="status" aria-live="polite"
         class="fixed right-4 top-16 z-50 w-[calc(100%-2rem)] max-w-sm overflow-hidden rounded-xl bg-white shadow-xl ring-1 {{ $ring }}">
        <div class="flex items-start gap-3 p-4">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $badge }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </span>
            <p class="flex-1 pt-1 text-sm font-medium text-gray-800">{{ $toast['message'] }}</p>
            <button type="button" @click="open = false" class="rounded p-1 text-gray-400 hover:text-gray-600" aria-label="{{ __('Close') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="h-1 {{ $bar }} origin-left" style="animation: toast-timer 5s linear forwards"></div>
    </div>
@endif
