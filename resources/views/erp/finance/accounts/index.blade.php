<x-layouts.erp title="Accounts">
    <div class="max-w-6xl">
        <x-erp.flash />

        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Company funding sources used to record income, expenses, and transfers.</p>
            @can('finance.manage')
                <a href="{{ route('finance.accounts.create') }}" class="inline-flex items-center rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-dark">
                    Add Account
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Account Number</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Opening Balance</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500">Current Balance</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        @can('finance.manage')
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $account->name }}</td>
                            <td class="px-4 py-3 text-gray-500 capitalize">{{ $account->account_type }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $account->account_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">Rp {{ number_format((float) $account->opening_balance, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">Rp {{ number_format($account->currentBalance(), 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @if ($account->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 text-green-700 px-2 py-0.5 text-xs font-medium">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium">Inactive</span>
                                @endif
                            </td>
                            @can('finance.manage')
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('finance.accounts.edit', $account) }}" class="text-brand hover:text-brand-dark font-medium">Edit</a>
                                    <form action="{{ route('finance.accounts.destroy', $account) }}" method="POST" class="inline" onsubmit="return confirm('Delete this account? This only works if it has no transactions.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-gray-400 hover:text-red-600 font-medium">Delete</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-400">No accounts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.erp>
