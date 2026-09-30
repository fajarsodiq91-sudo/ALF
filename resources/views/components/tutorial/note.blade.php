@props(['type' => 'info'])

@php
    $styles = [
        'info' => 'border-blue-200 bg-blue-50 text-blue-800',
        'tip' => 'border-green-200 bg-green-50 text-green-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
    ];
    $labels = ['info' => 'Info', 'tip' => 'Tips', 'warning' => 'Perhatian'];
@endphp

<div {{ $attributes->class(['rounded-md border px-4 py-3 text-sm', $styles[$type] ?? $styles['info']]) }}>
    <span class="font-semibold">{{ $labels[$type] ?? $labels['info'] }}:</span> {{ $slot }}
</div>
