@props(['href', 'active' => false, 'nested' => false, 'soon' => false, 'icon' => null])

@php($tab = $active && ! $soon && ! $nested)

<a
    href="{{ $soon ? '#' : $href }}"
    @if ($soon) onclick="return false;" @endif
    {{ $attributes->class([
        'relative flex items-center gap-2.5 rounded-lg text-sm transition-colors duration-200',
        'px-[13px] py-2' => ! $nested,
        'px-2.5 py-1.5 ml-[9px] pl-[23px] text-[12.5px] border-l border-white/10' => $nested,
        'sb-active-tab bg-steel-50 text-brand-dark font-semibold' => $tab,
        'bg-gradient-to-br from-brand-light to-brand-dark text-white' => $active && ! $soon && $nested,
        'text-steel-300 hover:bg-white/5 hover:text-white' => ! $active && ! $soon,
        'text-steel-500 cursor-not-allowed hover:bg-transparent hover:text-steel-500' => $soon,
    ]) }}
>
    @if ($icon)
        <x-erp.nav-icon :name="$icon" />
    @endif

    <span class="sb-label flex-1 truncate">{{ $slot }}</span>

    @if ($soon)
        <span class="sb-label ml-auto shrink-0 rounded-full bg-white/10 px-1.5 py-0.5 text-[9px] font-medium uppercase tracking-wide text-steel-400">
            Soon
        </span>
    @endif

    @if (! $nested)
        <span class="sb-tip">{{ $slot }}</span>
    @endif
</a>
