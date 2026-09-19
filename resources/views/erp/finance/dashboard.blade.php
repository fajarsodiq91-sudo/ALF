<x-layouts.erp title="Finance Dashboard">
    <div class="max-w-7xl">
        <x-erp.flash />

        <!-- Month Overview -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">This Month ({{ now()->format('F Y') }})</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-600">Income</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rp {{ number_format($monthlyIncome, 2, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-600">Expense</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rp {{ number_format($monthlyExpense, 2, ',', '.') }}
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-600">Net</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rp {{ number_format($monthlyNet, 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Year & Total Metrics -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Year-to-Date -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Year to Date</h3>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Income</span>
                            <span class="font-semibold text-green-600">Rp {{ number_format($yearIncome, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Expense</span>
                            <span class="font-semibold text-red-600">Rp {{ number_format($yearExpense, 2, ',', '.') }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">Net</span>
                            <span class="font-bold text-blue-600">Rp {{ number_format($yearNet, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- All Time -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">All Time</h3>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Total Income</span>
                            <span class="font-semibold text-green-600">Rp {{ number_format($totalIncome, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Total Expense</span>
                            <span class="font-semibold text-red-600">Rp {{ number_format($totalExpense, 2, ',', '.') }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">Net</span>
                            <span class="font-bold text-blue-600">Rp {{ number_format($totalNet, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Balance -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Balance</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        Rp {{ number_format($totalBalance, 2, ',', '.') }}
                    </p>
                </div>
            </div>

            <!-- Right Column: Recent Transactions & Account Balances -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Transactions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Recent Transactions</h3>
                        <a href="{{ route('finance.transactions') }}" class="text-sm text-blue-600 hover:text-blue-700">View all →</a>
                    </div>

                    @if ($recentTransactions->isEmpty())
                        <div class="px-6 py-8 text-center text-gray-500">
                            No transactions yet
                        </div>
                    @else
                        <div class="divide-y divide-gray-200">
                            @foreach ($recentTransactions as $txn)
                                <div class="px-6 py-4 border-b border-gray-100 last:border-b-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-700">
                                                    {{ ucfirst($txn['type']) }}
                                                </span>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $txn['description'] }}</p>
                                                    <p class="text-xs text-gray-500">{{ $txn['number'] }} • {{ $txn['date']->format('M d, Y') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            @if ($txn['type'] === 'income')
                                                +Rp {{ number_format($txn['amount'], 0, ',', '.') }}
                                            @elseif ($txn['type'] === 'expense')
                                                -Rp {{ number_format($txn['amount'], 0, ',', '.') }}
                                            @else
                                                Rp {{ number_format($txn['amount'], 0, ',', '.') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Account Summary -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Account Balances</h3>
                        <a href="{{ route('finance.reports.account-balances') }}" class="text-sm text-blue-600 hover:text-blue-700">View report →</a>
                    </div>

                    @if ($accountBalances->isEmpty())
                        <div class="px-6 py-8 text-center text-gray-500">
                            No accounts
                        </div>
                    @else
                        <div class="divide-y divide-gray-200">
                            @foreach ($accountBalances as $account)
                                <div class="px-6 py-4 hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $account['name'] }}</p>
                                            <p class="text-xs text-gray-500">Opening: Rp {{ number_format($account['opening_balance'], 0, ',', '.') }}</p>
                                        </div>
                                        <p class="text-lg font-bold text-blue-600">
                                            Rp {{ number_format($account['balance'], 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.erp>
