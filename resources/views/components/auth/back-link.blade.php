@props(['href'])

<p class="mt-6 text-center text-sm text-steel-500">
    <a href="{{ $href }}" class="inline-flex items-center gap-1 font-medium hover:text-brand transition-colors">&larr; {{ $slot }}</a>
</p>
