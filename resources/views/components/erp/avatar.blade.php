@props(['employee', 'size' => 'h-6 w-6'])

@if ($employee->photoUrl())
    <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}" title="{{ $employee->name }}" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-1 ring-gray-200']) }}>
@else
    <span title="{{ $employee->name }}" {{ $attributes->class([$size, 'inline-flex shrink-0 items-center justify-center rounded-full bg-brand/15 text-[0.65rem] font-semibold text-brand-dark ring-1 ring-gray-200']) }}>{{ $employee->initials() }}</span>
@endif
