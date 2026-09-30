<nav class="lg:col-span-1 space-y-4">
    <a href="{{ route('tutorial.index') }}"
       @class([
           'block rounded-md px-3 py-2 text-sm transition',
           'bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm' => $active === null,
           'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:shadow-sm' => $active !== null,
       ])>
        Ringkasan
    </a>

    @foreach ($groups as $group => $topics)
        <div class="space-y-1">
            <p class="px-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $group }}</p>
            @foreach ($topics as $slug => $topic)
                <a href="{{ route('tutorial.show', $slug) }}"
                   @class([
                       'block rounded-md px-3 py-2 text-sm transition',
                       'bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm' => $slug === $active,
                       'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:shadow-sm' => $slug !== $active,
                   ])>
                    {{ $topic['title'] }}
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
