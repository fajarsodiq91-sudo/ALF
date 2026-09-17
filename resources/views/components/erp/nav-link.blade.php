@props(['href', 'active' => false, 'nested' => false, 'soon' => false])

<a
    href="{{ $soon ? '#' : $href }}"
    @if ($soon) onclick="return false;" @endif
    {{ $attributes->class([
        'flex items-center justify-between rounded-md text-sm transition-colors',
        'px-3 py-2' => ! $nested,
        'px-3 py-1.5 ml-3 text-[13px]' => $nested,
        'bg-brand text-white' => $active && ! $soon,
        'text-gray-300 hover:bg-white/5 hover:text-white' => ! $active && ! $soon,
        'text-gray-500 cursor-not-allowed hover:bg-transparent hover:text-gray-500' => $soon,
    ]) }}
>
    <span>{{ $slot }}</span>

    @if ($soon)
        <span class="ml-2 shrink-0 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-gray-400">
            Coming Soon
        </span>
    @endif
</a>
