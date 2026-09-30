@props(['title', 'id' => null])

<section @if ($id) id="{{ $id }}" @endif class="bg-white rounded-lg shadow-md border border-gray-200 p-6 scroll-mt-20">
    <h2 class="text-base font-semibold text-gray-800">{{ $title }}</h2>
    <div class="mt-3 space-y-3 text-sm leading-relaxed text-gray-600 [&_strong]:font-semibold [&_strong]:text-gray-800 [&_a]:font-medium [&_a]:text-brand [&_a:hover]:text-brand-dark [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5 [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-xs [&_code]:text-gray-700">
        {{ $slot }}
    </div>
</section>
