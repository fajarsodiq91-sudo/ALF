<x-layouts.erp title="Edit Service Package">
    <div class="max-w-2xl bg-white rounded-lg shadow-md border border-gray-200 p-6">
        <form action="{{ route('services.packages.update', $package) }}" method="POST">
            @csrf
            @method('PUT')
            @include('erp.services.packages._form')
        </form>
    </div>
</x-layouts.erp>
