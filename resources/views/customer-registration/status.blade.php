<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $customer->isPendingApproval() ? 'Waiting for approval' : ($customer->isRejected() ? 'Registration update' : 'Registration approved') }} | PT Alfajar Logic Futura</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-steel-100 via-white to-brand-50">
        <div class="mx-auto max-w-xl px-4 py-8 sm:py-12">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('assets/icons/alf.png') }}" alt="" class="h-10 w-10 rounded">
                <span class="text-base font-semibold text-steel-800">PT Alfajar Logic Futura</span>
            </div>

            @php
                $pending = $customer->isPendingApproval();
                $rejected = $customer->isRejected();
                $approved = ! $pending && ! $rejected;
                $rupiah = fn ($amount) => \App\Services\SessionPaymentPlan::rupiah($amount);
            @endphp

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                {{-- Hero --}}
                <div @class([
                    'px-6 py-9 text-center text-white',
                    'bg-gradient-to-br from-steel-900 via-brand-dark to-brand-light' => ! $rejected,
                    'bg-gradient-to-br from-steel-900 to-steel-600' => $rejected,
                ])>
                    <div class="relative mx-auto flex h-16 w-16 items-center justify-center">
                        @if ($pending)<span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/30"></span>@endif
                        <span class="relative flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-lg">
                            @if ($rejected)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-steel-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @endif
                        </span>
                    </div>
                    @if ($pending)
                        <h1 class="mt-5 text-2xl font-bold">Thank you, {{ $customer->name }}!</h1>
                        <p class="mt-1 text-sm text-white/80">Your registration has been received.</p>
                        <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/30"><span class="h-2 w-2 rounded-full bg-amber-300"></span> Waiting for approval</span>
                    @elseif ($rejected)
                        <h1 class="mt-5 text-2xl font-bold">About your registration</h1>
                        <p class="mt-1 text-sm text-white/80">{{ $customer->name }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/30"><span class="h-2 w-2 rounded-full bg-gray-300"></span> Not approved</span>
                    @else
                        <h1 class="mt-5 text-2xl font-bold">Welcome, {{ $customer->name }}!</h1>
                        <p class="mt-1 text-sm text-white/80">Your registration has been approved.</p>
                        <span class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-white/30"><span class="h-2 w-2 rounded-full bg-green-300"></span> Approved</span>
                    @endif
                </div>

                <div class="px-6 py-6">
                    @if ($rejected)
                        <p class="text-sm text-gray-700">After reviewing your registration, we are unable to approve it at this time.</p>
                        @if ($customer->rejection_reason)
                            <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                <span class="font-medium">Reason:</span> {{ $customer->rejection_reason }}
                            </div>
                        @endif
                        <p class="mt-4 text-sm text-gray-500">If you believe this is a mistake, or you would like to register again, please contact us.</p>
                    @else
                        <h2 class="text-sm font-semibold text-gray-800">{{ $approved ? 'Your registration' : 'What happens next' }}</h2>

                        <ol class="mt-4 space-y-0">
                            <li class="relative flex gap-4 pb-6">
                                <span class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-brand/30"></span>
                                <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Registration received</p>
                                    <p class="text-xs text-gray-500">Your details and photo were saved.</p>
                                </div>
                            </li>
                            <li class="relative flex gap-4 pb-6">
                                <span @class(['absolute left-4 top-8 -ml-px h-full w-0.5', 'bg-brand/30' => $approved, 'bg-gray-200' => $pending])></span>
                                @if ($approved)
                                    <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                                @else
                                    <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50"><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-amber-500"></span></span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $approved ? 'Reviewed and approved by our team' : 'Our team reviews your registration' }}</p>
                                    <p class="text-xs text-gray-500">{{ $approved ? 'Approved on '.$customer->approved_at?->format('d M Y').'.' : 'We will prepare your program and meeting schedule. You are here.' }}</p>
                                </div>
                            </li>
                            <li class="relative flex gap-4">
                                @if ($approved)
                                    <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-light to-brand-dark text-white shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                                @else
                                    <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-gray-200 bg-white text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $approved ? 'Approval email sent' : 'You get an approval email' }}</p>
                                    <p class="text-xs text-gray-500">{{ $approved ? 'Sent to '.$customer->email.' with your details, schedule, and portal login.' : 'It contains your customer ID, your program schedule, and a link to log in to your customer portal.' }}</p>
                                </div>
                            </li>
                        </ol>
                    @endif

                    @if ($approved)
                        <div class="mt-6 rounded-lg bg-brand-50 px-4 py-4 text-center">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Your customer ID</p>
                            <p class="mt-1 font-mono text-3xl font-semibold text-brand">{{ $customer->customer_code }}</p>
                            <p class="mt-2 text-xs text-gray-500">Keep this ID and mention it on your next order. It is also your username for the portal.</p>
                        </div>

                        <a href="{{ route('portal.login') }}" class="mt-4 block rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition-all hover:from-brand-dark hover:to-brand-dark hover:shadow-md">Log in to your customer portal</a>
                        <p class="mt-2 text-center text-xs text-gray-500">Your initial password was sent to your email. You will be asked to choose a new one at your first login.</p>

                        @if ($sessions->isNotEmpty())
                            <div class="mt-6 space-y-3">
                                <h3 class="text-sm font-semibold text-gray-800">Your programs</h3>
                                @foreach ($sessions as $session)
                                    <div class="rounded-lg border border-gray-200 px-4 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $session->program->name }}</p>
                                        <ul class="mt-1 list-disc pl-5 text-xs text-gray-500">
                                            @foreach ($session->meetings as $meeting)
                                                <li>{{ $meeting->meeting_date->format('D, d M Y') }}@if ($meeting->timeRange()), {{ $meeting->timeRange() }}@endif</li>
                                            @endforeach
                                        </ul>
                                        @if ($session->payments->isNotEmpty())
                                            <div class="mt-2 rounded-md bg-brand-50 px-3 py-2 text-xs text-gray-600">
                                                <div class="font-medium text-gray-700">Fee {{ $rupiah($session->fee) }}</div>
                                                @foreach ($session->payments as $payment)
                                                    <div>{{ $payment->label }}: {{ $rupiah($payment->amount) }} — {{ $payment->dueLabel() }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if ($pending && $requested)
                        <div class="mt-6 rounded-lg border border-gray-200 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-800">Programs and dates you asked for</h3>
                            <p class="text-xs text-gray-500">Our team will confirm the final schedule when they approve your registration.</p>
                            <ul class="mt-2 space-y-2 text-sm">
                                @foreach ($requested as $entry)
                                    <li>
                                        <span class="font-medium text-gray-800">{{ $entry['program']->name }}</span>
                                        <ul class="mt-0.5 list-disc pl-5 text-gray-500">
                                            @foreach ($entry['meetings'] as $meeting)<li>{{ $meeting['label'] }}</li>@endforeach
                                        </ul>
                                        @if ($entry['payments'])
                                            <div class="mt-1 rounded-md bg-brand-50 px-3 py-2 text-xs text-gray-600">
                                                <div class="font-medium text-gray-700">Fee {{ $rupiah($entry['price']) }}@if ($entry['group_size'] > 1) <span class="font-normal text-gray-500">({{ $entry['group_size'] }} people × {{ $rupiah($entry['per_person']) }})</span>@endif</div>
                                                @foreach ($entry['payments'] as $payment)
                                                    <div>{{ $payment['label'] }}: {{ $rupiah($payment['amount']) }} — {{ $payment['when'] }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($pending)
                        <div class="mt-6 flex gap-3 rounded-lg bg-brand-50 px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            <p class="text-sm text-gray-700">
                                A confirmation email was sent to <span class="font-semibold">{{ $customer->email }}</span>.
                                <span class="block text-xs text-gray-500">Please also check your spam folder. Bookmark this page to see when your registration is approved.</span>
                            </p>
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 text-center">
                    <p class="text-xs text-gray-500">Questions? We are happy to help.</p>
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm font-medium">
                        <a href="mailto:admin@alfajarlogic.com" class="text-brand hover:text-brand-dark">admin@alfajarlogic.com</a>
                        <a href="https://wa.me/6282125298452" target="_blank" rel="noopener noreferrer" class="text-brand hover:text-brand-dark">WhatsApp</a>
                        <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700">Visit our website</a>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">&copy; {{ date('Y') }} PT Alfajar Logic Futura</p>
        </div>
    </body>
</html>
