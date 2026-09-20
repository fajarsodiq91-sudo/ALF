<x-layouts.erp title="Account Balances Report">
    <div class="max-w-4xl">
        <x-erp.flash />

        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Detailed balance for each account including income, expenses, and transfers.</p>
            </div>
            <a href="{{ route('finance.reports.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                ← Back to Reports
            </a>
        </div>

        @if ($balances->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-6 py-12 text-center text-gray-500">
                No accounts
            </div>
        @else
            <div class="space-y-4">
                @foreach ($balances as $account)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $account['account'] }}</h3>
                            <p class="text-2xl font-bold text-blue-600">
                                Rp {{ number_format($account['balance'], 2, ',', '.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <p class="text-sm text-gray-600">Initial Balance</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    Rp {{ number_format($account['initial'], 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="px-6 py-4 border-l border-gray-200">
                                <p class="text-sm text-gray-600">Income</p>
                                <p class="mt-1 text-lg font-semibold text-green-600">
                                    +Rp {{ number_format($account['income'], 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="px-6 py-4 border-l border-gray-200">
                                <p class="text-sm text-gray-600">Expense</p>
                                <p class="mt-1 text-lg font-semibold text-red-600">
                                    -Rp {{ number_format($account['expense'], 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="px-6 py-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">Transfers In</p>
                                <p class="mt-1 text-lg font-semibold text-blue-600">
                                    +Rp {{ number_format($account['transfers_in'], 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="px-6 py-4 border-t border-l border-gray-200">
                                <p class="text-sm text-gray-600">Transfers Out</p>
                                <p class="mt-1 text-lg font-semibold text-orange-600">
                                    -Rp {{ number_format($account['transfers_out'], 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="px-6 py-4 border-t border-gray-200 col-span-2">
                                <p class="text-sm text-gray-600">Loans (net cash effect)</p>
                                <p class="mt-1 text-lg font-semibold {{ $account['loans'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                                    {{ $account['loans'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($account['loans']), 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Total All Accounts</h3>
                        <p class="text-2xl font-bold text-blue-600">
                            Rp {{ number_format($balances->sum('balance'), 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.erp>
