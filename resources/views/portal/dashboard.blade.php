<x-layouts.portal title="My Programs">
    @php $inputClass = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm'; @endphp

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 flex items-center gap-4">
            @if ($customer->photoUrl())
                <img src="{{ $customer->photoUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
            @endif
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Welcome, {{ $customer->name }}</h1>
                <p class="text-sm text-gray-500">Customer ID <span class="font-mono text-gray-700">{{ $customer->customer_code }}</span></p>
            </div>
        </div>

        @if (\App\Services\OperatingHours::schedule())
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6" x-data="{ open: false }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between text-left">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Our operating hours</span>
                        <span class="block text-xs text-gray-500">Meetings take place on these days and times. Green slots are still available.</span>
                    </span>
                    <span class="shrink-0 pl-3 text-sm font-medium text-brand" x-text="open ? 'Collapse ▲' : 'Expand ▼'"></span>
                </button>
                <div x-show="open" x-cloak class="mt-3">
                    @include('erp.partials.availability-calendar', ['booked' => \App\Services\BookedSlots::keys($customer->id)])
                    <h3 class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wide text-gray-500">Weekly hours</h3>
                    <div x-data='{ days: @json(\App\Services\OperatingHours::forWeekCalendar()) }'>
                        @include('erp.partials.week-calendar')
                    </div>
                </div>
            </div>
        @endif

        @forelse ($sessions as $session)
            @php
                $done = $session->meetings->where('is_completed', true)->count();
                $total = $session->meetings->count();
                $percent = $total ? (int) round($done / $total * 100) : 0;
                $sessionProjects = $projects[$session->id] ?? collect();
            @endphp

            <section class="bg-white rounded-lg shadow-md border border-gray-200 transition-shadow hover:shadow-lg">
                <div class="p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800">{{ $session->program->name }}</h2>
                            <p class="text-sm text-gray-500">
                                {{ \App\Services\MasterData::label('program_type', $session->program->program_type) }}
                                · {{ \App\Services\MasterData::label('delivery_mode', $session->delivery_mode) }}
                                @if ($session->location) · {{ $session->location }} @endif
                                @if ($session->instructor) · Instructor: {{ $session->instructor->name }} @endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if ($session->materials_url)
                                <a href="{{ $session->materials_url }}" target="_blank" rel="noopener noreferrer" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:shadow-md transition">Learning materials</a>
                            @endif
                            @if ($session->certificate_url)
                                <a href="{{ $session->certificate_url }}" target="_blank" rel="noopener noreferrer" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:shadow-md transition">Certificate</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Progress</span><span>{{ $done }} of {{ $total }} meetings done</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-gray-200"><div class="h-2 rounded-full bg-gradient-to-r from-brand to-brand-light" style="width: {{ $percent }}%"></div></div>
                    </div>
                </div>

                <div class="overflow-x-auto border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Time</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Place</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Topic</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($session->meetings as $meeting)
                                <tr>
                                    <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $meeting->meeting_date->format('D, d M Y') }}</td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $meeting->timeRange() ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $meeting->location ?: ($session->location ?: '—') }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $meeting->topic ?: '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if ($meeting->is_completed)
                                            <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Done</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium">Upcoming</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">The schedule will be shared soon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($session->customer_id === $customer->id && $session->payments->isNotEmpty())
                    <div class="border-t border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-800">Payments <span class="font-normal text-gray-500">&middot; total {{ \App\Services\SessionPaymentPlan::rupiah($session->fee) }}</span></h3>
                        <ul class="mt-3 divide-y divide-gray-100 rounded-md border border-gray-200 text-sm">
                            @foreach ($session->payments as $payment)
                                <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <div>
                                        <span class="font-medium text-gray-800">{{ $payment->label }}</span>
                                        <span class="text-xs text-gray-400">&middot; {{ $payment->dueLabel() }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-800">{{ \App\Services\SessionPaymentPlan::rupiah($payment->amount) }}</span>
                                        @if ($payment->isPaid())
                                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Paid</span>
                                        @elseif ($payment->isDue())
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Due</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Upcoming</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($session->customer_id === $customer->id && $session->acceptsParticipants())
                    <div class="border-t border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-800">Participants <span class="font-normal text-gray-500">&middot; {{ $session->participants->count() }} of {{ $session->participant_limit }}</span></h3>
                        <p class="text-xs text-gray-500">Share this link with your employees. Each one fills in their name and email and gets their own portal login.</p>
                        @if ($session->participantLinkOpen())
                            <input type="text" readonly value="{{ route('participant.show', $session->participant_token) }}" onclick="this.select()" class="mt-2 {{ $inputClass }}">
                        @else
                            <p class="mt-2 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500">The link is closed: the session is full or no longer open.</p>
                        @endif
                        @if ($session->participants->isNotEmpty())
                            <ul class="mt-3 divide-y divide-gray-100 rounded-md border border-gray-200 text-sm">
                                @foreach ($session->participants as $participant)
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                        <span class="font-medium text-gray-800">{{ $participant->name }}</span>
                                        <span class="text-xs text-gray-400">{{ $participant->email }} · {{ $participant->customer_code }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <div class="border-t border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-800">Your project</h3>
                    <p class="text-xs text-gray-500">Upload the project you built in this program. We may add it to our portfolio.</p>

                    @if ($sessionProjects->isNotEmpty())
                        <ul class="mt-3 divide-y divide-gray-100 rounded-md border border-gray-200 text-sm">
                            @foreach ($sessionProjects as $project)
                                <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                    <div>
                                        <span class="font-medium text-gray-800">{{ $project->title }}</span>
                                        <span class="text-xs text-gray-400">· {{ $project->created_at->format('d M Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if ($project->in_portfolio)
                                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Added to portfolio</span>
                                        @endif
                                        @if ($project->file_path)
                                            <a href="{{ route('portal.projects.download', $project) }}" class="text-brand hover:text-brand-dark font-medium">Download</a>
                                        @endif
                                        @if ($project->external_url)
                                            <a href="{{ $project->external_url }}" target="_blank" rel="noopener noreferrer" class="text-brand hover:text-brand-dark font-medium">Open link</a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (session()->has('portal_preview'))
                        <p class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">Uploading is disabled in preview mode.</p>
                    @else
                    <form action="{{ route('portal.projects.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf
                        <input type="hidden" name="training_session_id" value="{{ $session->id }}">
                        @php $mine = (int) old('training_session_id') === $session->id; @endphp
                        <div class="sm:col-span-2">
                            <input type="text" name="title" value="{{ $mine ? old('title') : '' }}" placeholder="Project title" required class="{{ $inputClass }}">
                            @if ($mine) @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="sm:col-span-2">
                            <textarea name="description" rows="2" placeholder="Short description (optional)" class="{{ $inputClass }}">{{ $mine ? old('description') : '' }}</textarea>
                        </div>
                        <div>
                            <input type="file" name="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
                            <p class="mt-1 text-xs text-gray-500">ZIP, PDF, Office file, or image. Max 10 MB.</p>
                            @if ($mine) @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div>
                            <input type="url" name="external_url" value="{{ $mine ? old('external_url') : '' }}" placeholder="…or a link (Google Drive, GitHub, …)" class="{{ $inputClass }}">
                            @if ($mine) @error('external_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-brand-dark hover:to-brand-dark hover:shadow-md transition-all">Upload project</button>
                        </div>
                    </form>
                    @endif
                </div>
            </section>
        @empty
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-8 text-center text-gray-500">
                You have no programs yet. Your schedule will appear here once it is arranged.
            </div>
        @endforelse
    </div>
</x-layouts.portal>
