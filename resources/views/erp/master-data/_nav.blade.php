<nav class="lg:col-span-1 space-y-1">
    @foreach ($groups as $key => $definition)
        <a href="{{ route('masterdata.index', ['group' => $key]) }}"
           @class([
               'block rounded-md px-3 py-2 text-sm transition',
               'bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm' => $key === $active,
               'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:shadow-sm' => $key !== $active,
           ])>
            {{ $definition['label'] }}
        </a>
    @endforeach
    <a href="{{ route('masterdata.hours.edit') }}"
       @class([
           'mt-3 block rounded-md px-3 py-2 text-sm transition',
           'bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm' => $active === 'operating-hours',
           'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:shadow-sm' => $active !== 'operating-hours',
       ])>
        Operating Hours
    </a>
    <a href="{{ route('masterdata.agreement.edit') }}"
       @class([
           'block rounded-md px-3 py-2 text-sm transition',
           'bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm' => $active === 'agreement',
           'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:shadow-sm' => $active !== 'agreement',
       ])>
        Agreement
    </a>
</nav>
