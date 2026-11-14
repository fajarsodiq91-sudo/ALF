@php
    $rupiah = fn ($amount) => \App\Services\SessionPaymentPlan::rupiah($amount);
    $steps = ['brief' => 'Brief', 'quoted' => 'Quote', 'accepted' => 'Down payment', 'in_progress' => 'In progress', 'delivered' => 'Review', 'completed' => 'Completed'];
    $keys = array_keys($steps);
    $reached = array_search($order->status, $keys, true);
    $btn = 'inline-flex items-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition';
    $finalLocked = $order->files->where('kind', 'final')->isNotEmpty() && ! $order->isFullyPaid();
@endphp
<x-layouts.portal :title="$order->title">
    <a href="{{ route('portal.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; My orders</a>

    <div class="mt-3 space-y-5">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h1 class="text-lg font-semibold text-gray-800">{{ $order->title }}</h1>
                    <p class="text-xs text-gray-500">{{ $order->orderNumber() }} · {{ $order->package?->name ?? 'Custom' }} · sent {{ $order->created_at->format('d M Y') }}</p>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $order->statusStyle() }}">{{ $order->statusLabel() }}</span>
            </div>

            @if ($reached !== false)
                <ol class="mt-5 grid grid-cols-6 gap-1 text-center text-[11px]">
                    @foreach ($steps as $key => $label)
                        <li class="{{ array_search($key, $keys, true) <= $reached ? 'text-brand font-semibold' : 'text-gray-400' }}">
                            <div class="mx-auto mb-1 h-1.5 rounded-full {{ array_search($key, $keys, true) <= $reached ? 'bg-brand' : 'bg-gray-200' }}"></div>{{ $label }}
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($order->hasStatus('declined'))
                <p class="mt-4 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600">You declined this quote.@if ($order->decline_reason) Reason: {{ $order->decline_reason }}@endif</p>
            @elseif ($order->hasStatus('cancelled'))
                <p class="mt-4 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-600">This order was cancelled.</p>
            @elseif ($order->hasStatus('brief'))
                <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">We are reviewing your brief and will send you a quote by email and here.</p>
            @elseif ($order->hasStatus('in_progress') && $order->openRevisions() > 0)
                <p class="mt-4 rounded-md bg-brand-50 px-3 py-2 text-sm text-gray-700">We are working on your change request.</p>
            @endif
        </div>

        {{-- The quote --}}
        @if ($order->quote_total)
            <div class="rounded-lg border {{ $order->hasStatus('quoted') ? 'border-blue-300 ring-1 ring-blue-200' : 'border-gray-200' }} bg-white p-6 shadow-md">
                <h2 class="text-sm font-semibold text-gray-800">Quote</h2>
                <table class="mt-3 w-full text-sm">
                    @foreach ($order->quote_items as $item)
                        <tr class="border-b border-gray-100"><td class="py-1.5 text-gray-700">{{ $item['label'] }}</td><td class="py-1.5 text-right text-gray-800">{{ $rupiah($item['amount']) }}</td></tr>
                    @endforeach
                    @if ($order->hasDiscount())
                        <tr><td class="pt-2 text-gray-600">Subtotal</td><td class="pt-2 text-right text-gray-600">{{ $rupiah($order->quoteSubtotal()) }}</td></tr>
                        <tr><td class="text-green-700">Discount @if ($order->discount_label)({{ $order->discount_label }}) @endif· {{ $order->discountDescription() }}</td><td class="text-right text-green-700">- {{ $rupiah($order->discount_amount) }}</td></tr>
                    @endif
                    <tr><td class="pt-2 font-semibold text-gray-800">Total</td><td class="pt-2 text-right font-semibold text-gray-900">{{ $rupiah($order->quote_total) }}</td></tr>
                </table>
                <dl class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600 sm:grid-cols-2">
                    <div>Payment: {{ $order->dp_percentage >= 100 ? '100% upfront' : $order->dp_percentage.'% down payment, '.(100 - $order->dp_percentage).'% on delivery' }}</div>
                    <div>Estimated time: {{ $order->estimated_days ? $order->estimated_days.' working days after the down payment' : 'to be agreed' }}</div>
                    <div>Revisions included: {{ $order->included_revisions }}</div>
                    @if ($order->quote_valid_until)<div>Valid until {{ $order->quote_valid_until->format('d M Y') }}</div>@endif
                </dl>
                @if ($order->quote_notes)<p class="mt-3 whitespace-pre-line text-sm text-gray-600">{{ $order->quote_notes }}</p>@endif

                @if ($order->hasStatus('quoted'))
                    @if ($order->quoteExpired())
                        <p class="mt-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">This quote has expired. Please contact us for a new one.</p>
                    @else
                        <div class="mt-4 flex flex-wrap items-start gap-3">
                            <form action="{{ route('portal.orders.accept', $order) }}" method="POST" onsubmit="return confirm('Accept this quote? We will send you the down-payment invoice.');">
                                @csrf
                                <button type="submit" class="{{ $btn }} bg-gradient-to-br from-brand-light to-brand-dark text-white hover:from-brand-dark hover:to-brand-dark">Accept quote</button>
                            </form>
                            <form action="{{ route('portal.orders.decline', $order) }}" method="POST" x-data="{ open: false }" class="flex-1 min-w-[12rem]">
                                @csrf
                                <button type="button" x-show="!open" @click="open = true" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Decline</button>
                                <div x-show="open" x-cloak class="space-y-2">
                                    <textarea name="decline_reason" rows="2" maxlength="1000" placeholder="Reason (optional)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand"></textarea>
                                    <button type="submit" class="{{ $btn }} border border-gray-300 bg-white text-red-600 hover:bg-red-50">Confirm decline</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- Payments --}}
        @if ($order->payments->isNotEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md">
                <h2 class="text-sm font-semibold text-gray-800">Payments</h2>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    @foreach ($order->payments as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <div>
                                <span class="text-gray-800">{{ $payment->label }}</span>
                                <span class="block text-xs text-gray-500">{{ $payment->invoiceNumber() }}@if (! $payment->isPaid()) · due {{ strtolower($payment->dueLabel()) }}@endif</span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-800">{{ $rupiah($payment->amount) }}</span>
                                @if ($payment->isPaid())
                                    <span class="ml-2 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Paid {{ $payment->paid_date?->format('d M Y') }}</span>
                                @elseif ($payment->isDue())
                                    <span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Due</span>
                                @else
                                    <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Upcoming</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($order->payments->contains(fn ($payment) => ! $payment->isPaid() && $payment->isDue()))
                    <p class="mt-3 rounded-md bg-brand-50 px-3 py-2 text-xs text-gray-600">
                        Please transfer to: <span class="whitespace-pre-line font-medium">{{ \App\Models\Setting::get('company_bank_account') ?: 'the account in your invoice email' }}</span>
                        <span class="block">Then send the transfer proof to us (reply to the invoice email or WhatsApp). We confirm it here.</span>
                    </p>
                @endif
            </div>
        @endif

        {{-- Delivered work --}}
        @php $delivered = $order->files->whereIn('kind', ['draft', 'final']); @endphp
        @if ($delivered->isNotEmpty())
            <div class="rounded-lg border {{ $order->hasStatus('delivered') ? 'border-teal-300 ring-1 ring-teal-200' : 'border-gray-200' }} bg-white p-6 shadow-md">
                <h2 class="text-sm font-semibold text-gray-800">Your files</h2>
                @if ($finalLocked)
                    <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">The final files unlock once every invoice of this order is paid.</p>
                @endif
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    @foreach ($delivered as $file)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <div>
                                <span class="text-gray-800">{{ $file->original_name }}</span>
                                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ \App\Models\ServiceOrderFile::KINDS[$file->kind] }}</span>
                                @if ($file->note)<span class="block text-xs text-gray-500">{{ $file->note }}</span>@endif
                            </div>
                            @if ($file->releasedToCustomer())
                                <a href="{{ route('portal.orders.files.download', $file) }}" class="text-sm font-medium text-brand hover:text-brand-dark">Download</a>
                            @else
                                <span class="text-xs text-gray-400">Locked until paid</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Revisions --}}
        @if ($order->acceptsRevisionRequests() || $order->revisions->isNotEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md">
                <h2 class="text-sm font-semibold text-gray-800">Changes</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $order->revisionsUsed() }} of {{ $order->included_revisions }} included revision(s) used.</p>
                @foreach ($order->revisions as $revision)
                    <div class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-sm">
                        <p class="whitespace-pre-line text-gray-700">{{ $revision->message }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $revision->created_at->format('d M Y') }} · {{ $revision->isResolved() ? 'done' : 'in progress' }}@if ($revision->is_extra) · beyond the included revisions: we may quote an extra charge @endif</p>
                    </div>
                @endforeach
                @if ($order->acceptsRevisionRequests())
                    <form action="{{ route('portal.orders.revisions.store', $order) }}" method="POST" class="mt-4 space-y-2">
                        @csrf
                        <label for="message" class="block text-sm font-medium text-gray-700">Ask for changes</label>
                        <textarea name="message" id="message" rows="3" required maxlength="3000" placeholder="Describe what to change. Please list everything in one request." class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                        @error('message') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Send change request</button>
                        <p class="text-xs text-gray-500">Happy with it? Just pay the final invoice and the final files are released.</p>
                    </form>
                @endif
            </div>
        @endif

        {{-- The brief --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md">
            <h2 class="text-sm font-semibold text-gray-800">Your brief</h2>
            <dl class="mt-3 space-y-3 text-sm">
                @foreach (['purpose' => 'Purpose', 'kpis' => 'KPIs', 'data_sources' => 'Data sources', 'data_volume' => 'Data volume', 'excel_version' => 'Excel version', 'notes' => 'Notes'] as $field => $label)
                    @if ($order->{$field})
                        <div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="whitespace-pre-line text-gray-800">{{ $order->{$field} }}</dd></div>
                    @endif
                @endforeach
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-600">
                    @if ($order->deadline)<span>Deadline {{ $order->deadline->format('d M Y') }}</span>@endif
                    @if ($order->timeline_days)<span>Requested turnaround: {{ $order->timeline_days }} working days</span>@endif
                    <span>{{ $order->needs_refresh ? 'Regular data refresh needed' : 'One-time data' }}</span>
                </div>
                @if ($order->addons)
                    <div><dt class="text-xs text-gray-500">Add-ons</dt><dd class="text-gray-800">{{ collect($order->addons)->pluck('label')->implode(', ') }}</dd></div>
                @endif
            </dl>

            @php $briefFiles = $order->files->where('kind', 'brief'); @endphp
            @if ($briefFiles->isNotEmpty())
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($briefFiles as $file)
                        <li><a href="{{ route('portal.orders.files.download', $file) }}" class="text-brand hover:text-brand-dark">{{ $file->original_name }}</a></li>
                    @endforeach
                </ul>
            @endif

            @if ($order->isOpen())
                <form action="{{ route('portal.orders.files.store', $order) }}" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="files[]" multiple required class="text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand">
                    <button type="submit" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Add files</button>
                    @error('files') <p class="w-full text-sm text-red-600">{{ $message }}</p> @enderror
                    @foreach ($errors->get('files.*') as $messages) @foreach ($messages as $message)<p class="w-full text-sm text-red-600">{{ $message }}</p>@endforeach @endforeach
                </form>
            @endif

            @if ($order->hasStatus('brief', 'quoted'))
                <form action="{{ route('portal.orders.cancel', $order) }}" method="POST" class="mt-4" onsubmit="return confirm('Withdraw this request?');">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-red-600">Withdraw this request</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.portal>
