<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Custom Work Request | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-xl px-4 py-8">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
                <h1 class="text-lg font-semibold text-gray-800">Request custom work</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Tell us who you are and what you need built (for example an Excel dashboard). We review your brief and send you a quote;
                    you only pay after you accept it. Registration type: <span class="font-medium text-gray-700">{{ \App\Services\MasterData::label('customer_type', $customer->customer_type) }}</span>.
                </p>

                @if ($errors->any())
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">Please check the highlighted fields and try again.</div>
                @endif

                <form action="{{ route('customer-registration.store', $customer->registration_token) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <h2 class="text-sm font-semibold text-gray-800">About you</h2>
                    @foreach ([['name', 'Name / Company Name', 'text', true], ['email', 'Email', 'email', true], ['phone', 'Phone / WhatsApp', 'text', true], ['city', 'City', 'text', false]] as [$field, $label, $type, $required])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}@if ($required) <span class="text-red-600">*</span>@endif</label>
                            <input type="{{ $type }}" name="{{ $field }}" id="{{ $field }}" value="{{ old($field) }}" @required($required) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                            @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('address') }}</textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="photo" class="block text-sm font-medium text-gray-700">Photo (optional)</label>
                        <input type="file" name="photo" id="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand hover:file:bg-brand-100">
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <hr class="border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-800">Your request</h2>
                    @include('service-orders.brief-fields', ['packages' => $packages])

                    <button type="submit" class="w-full rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all duration-150">Submit request</button>
                </form>
            </div>
        </div>
    </body>
</html>
