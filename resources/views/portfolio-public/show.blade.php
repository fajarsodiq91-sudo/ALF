<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $customer->name }} — Portfolio | PT Alfajar Logic Futura</title>
        <meta name="description" content="Certificates and showcased work by {{ $customer->name }}, delivered with PT Alfajar Logic Futura.">
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                <div class="bg-gradient-to-br from-steel-900 via-brand-dark to-brand-light px-6 py-8 text-center text-white">
                    @if ($customer->photoUrl())
                        <img src="{{ $customer->photoUrl() }}" alt="" class="mx-auto h-20 w-20 rounded-full object-cover ring-4 ring-white/30">
                    @else
                        <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-2xl font-semibold ring-4 ring-white/30">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                    @endif
                    <h1 class="mt-4 text-2xl font-bold">{{ $customer->name }}</h1>
                    <p class="mt-1 text-sm text-white/80">Portfolio &amp; certificates</p>
                </div>

                <div class="px-6 py-6 space-y-8">
                    <section>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Certificates earned</h2>
                        @if ($certificates->isEmpty())
                            <p class="mt-2 text-sm text-gray-400">No certificates published yet.</p>
                        @else
                            <ul class="mt-3 space-y-2">
                                @foreach ($certificates as $certificate)
                                    <li class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">{{ $certificate->session->program->name }}</p>
                                            <p class="font-mono text-xs text-gray-500">{{ $certificate->number }} · issued {{ $certificate->issued_at->format('d M Y') }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Completed</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Showcased projects</h2>
                        @if ($projects->isEmpty())
                            <p class="mt-2 text-sm text-gray-400">No projects showcased yet.</p>
                        @else
                            <ul class="mt-3 space-y-3">
                                @foreach ($projects as $project)
                                    <li class="rounded-md border border-gray-200 p-4">
                                        <p class="text-sm font-semibold text-gray-800">{{ $project->title }}</p>
                                        @if ($project->description)
                                            <p class="mt-1 text-sm text-gray-600">{{ $project->description }}</p>
                                        @endif
                                        <div class="mt-2 flex flex-wrap gap-3 text-sm">
                                            @if ($project->external_url)
                                                <a href="{{ $project->external_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand hover:text-brand-dark">View link &rarr;</a>
                                            @endif
                                            @if ($project->file_path)
                                                <a href="{{ route('customer-portfolio.projects.download', $project) }}" class="font-medium text-brand hover:text-brand-dark">Download {{ $project->file_name ?: 'file' }} &rarr;</a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">Issued and verified by PT Alfajar Logic Futura.</p>
        </div>
    </body>
</html>
