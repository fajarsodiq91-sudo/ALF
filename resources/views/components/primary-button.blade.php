<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-br from-brand-light to-brand-dark border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-brand-dark hover:to-brand-dark focus:from-brand-dark focus:to-brand-dark active:to-brand-950 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
