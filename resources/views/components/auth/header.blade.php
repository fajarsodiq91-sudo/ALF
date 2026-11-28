@props(['eyebrow' => null, 'title', 'subtitle' => null])

<div class="mb-7">
    @if ($eyebrow)
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">{{ $eyebrow }}</p>
    @endif
    <h1 class="mt-1 text-2xl font-bold text-steel-900">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-1.5 text-sm leading-relaxed text-steel-500">{{ $subtitle }}</p>
    @endif
</div>
