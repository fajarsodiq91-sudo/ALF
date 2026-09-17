<x-layouts.erp title="Financial Reports">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="mb-4">
            <p class="text-sm text-gray-500">Overview of income, expenses, and account balances.</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Income -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Income</p>
                        <p class="mt-2 text-2xl font-bold text-green-600">
                            Rp {{ number_format($incomeTotal, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-4xl text-green-200">📊</div>
                </div>
            </div>

            <!-- Total Expense -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Expense</p>
                        <p class="mt-2 text-2xl font-bold text-red-600">
                            Rp {{ number_format($expenseTotal, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-4xl text-red-200">💸</div>
                </div>
            </div>

            <!-- Net Income -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Net Income</p>
                        <p class="mt-2 text-2xl font-bold {{ $netIncome >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                            Rp {{ number_format($netIncome, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-4xl {{ $netIncome >= 0 ? 'text-blue-200' : 'text-red-200' }}">💰</div>
                </div>
            </div>

            <!-- Total Balance -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Balance</p>
                        <p class="mt-2 text-2xl font-bold text-blue-600">
                            Rp {{ number_format($accountBalances->sum('balance'), 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="text-4xl text-blue-200">🏦</div>
                </div>
            </div>
        </div>

        <!-- Reports Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Account Balances -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Account Balances</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse ($accountBalances as $account)
                        <div class="px-6 py-4 flex justify-between items-center hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">{{ $account['account'] }}</p>
                                <p class="text-sm text-gray-500">Initial: Rp {{ number_format($account['initial'], 2, ',', '.') }}</p>
                            </div>
                            <p class="text-lg font-semibold text-blue-600">
                                Rp {{ number_format($account['balance'], 2, ',', '.') }}
                            </p>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500">
                            No accounts
                        </div>
                    @endforelse
                </div>
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                    <a href="{{ route('finance.reports.account-balances') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                        View detailed report →
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reports</h3>
                <div class="space-y-3">
                    <a href="{{ route('finance.reports.income-by-category') }}" class="block p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">Income by Category</p>
                                <p class="text-sm text-gray-500">Breakdown of income sources</p>
                            </div>
                            <span class="text-gray-400">→</span>
                        </div>
                    </a>

                    <a href="{{ route('finance.reports.expense-by-category') }}" class="block p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">Expense by Category</p>
                                <p class="text-sm text-gray-500">Breakdown of expenses</p>
                            </div>
                            <span class="text-gray-400">→</span>
                        </div>
                    </a>

                    <a href="{{ route('finance.reports.monthly-flow') }}" class="block p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">Monthly Cash Flow</p>
                                <p class="text-sm text-gray-500">Income vs expenses by month</p>
                            </div>
                            <span class="text-gray-400">→</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.erp>
