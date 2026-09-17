<x-layouts.erp title="Dashboard">
    <div class="max-w-5xl space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800">Welcome, {{ auth()->user()->name }}.</h2>
            <p class="mt-1 text-sm text-gray-500">
                Role: {{ auth()->user()->getRoleNames()->join(', ') ?: 'No role assigned' }}
            </p>
        </div>

        @canany(['finance.view', 'finance.manage', 'finance.reports'])
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Finance</h3>
                <p class="text-sm text-gray-500 mb-4">
                    The Finance module is the first module being built. Its dashboard, accounts, and transactions
                    are not populated yet — that work happens in the upcoming Finance phases.
                </p>
                <a href="{{ route('finance.dashboard') }}" class="inline-flex items-center text-sm font-medium text-brand hover:text-brand-dark">
                    Go to Finance &rarr;
                </a>
            </div>
        @endcanany

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-800 mb-1">Other modules</h3>
            <p class="text-sm text-gray-500">
                Sales, Training, Projects, HR, and Assets are planned modules, shown in the sidebar as
                "Coming Soon" until each one is built in its own phase.
            </p>
        </div>
    </div>
</x-layouts.erp>
