<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'ERP' }} | PT Alfajar Logic Futura</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/icons/alf.svg') }}" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen flex">
            {{-- Sidebar --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-300 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="h-16 flex items-center gap-2 px-5 border-b border-white/10">
                    <img src="{{ asset('assets/icons/alf.svg') }}" alt="" class="h-8 w-8 rounded" />
                    <span class="font-semibold text-white leading-tight text-sm">
                        Alfajar Logic<br class="hidden" />
                        <span class="text-brand-50/70 font-normal text-xs">ERP</span>
                    </span>
                </div>

                <nav class="py-4 px-3 space-y-1 overflow-y-auto" style="height: calc(100% - 4rem);">
                    <x-erp.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-erp.nav-link>

                    @canany(['finance.view', 'finance.manage', 'finance.reports'])
                        <x-erp.nav-group label="Finance" :active="request()->routeIs('finance.*')">
                            <x-erp.nav-link :href="route('finance.dashboard')" :active="request()->routeIs('finance.dashboard')" nested>Dashboard</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.accounts')" :active="request()->routeIs('finance.accounts')" nested>Accounts</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.categories')" :active="request()->routeIs('finance.categories')" nested>Categories</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.income')" :active="request()->routeIs('finance.income')" nested>Income</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.expenses')" :active="request()->routeIs('finance.expenses')" nested>Expenses</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.transfers')" :active="request()->routeIs('finance.transfers')" nested>Transfers</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.transactions')" :active="request()->routeIs('finance.transactions')" nested>Transactions</x-erp.nav-link>
                            <x-erp.nav-link :href="route('finance.reports.index')" :active="request()->routeIs('finance.reports.*')" nested>Reports</x-erp.nav-link>
                        </x-erp.nav-group>
                    @endcanany

                    <x-erp.nav-link :href="route('sales.index')" :active="request()->routeIs('sales.*')" soon>Sales</x-erp.nav-link>
                    <x-erp.nav-link :href="route('training.index')" :active="request()->routeIs('training.*')" soon>Training</x-erp.nav-link>
                    <x-erp.nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" soon>Projects</x-erp.nav-link>
                    <x-erp.nav-link :href="route('hr.index')" :active="request()->routeIs('hr.*')" soon>HR</x-erp.nav-link>
                    <x-erp.nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')" soon>Assets</x-erp.nav-link>

                    @canany(['settings.manage-users', 'settings.manage-roles', 'settings.manage-system'])
                        <x-erp.nav-group label="Settings" :active="request()->routeIs('settings.*')">
                            @can('settings.manage-users')
                                <x-erp.nav-link :href="route('settings.users')" :active="request()->routeIs('settings.users')" nested soon>Users</x-erp.nav-link>
                            @endcan
                            @can('settings.manage-roles')
                                <x-erp.nav-link :href="route('settings.roles')" :active="request()->routeIs('settings.roles')" nested soon>Roles</x-erp.nav-link>
                            @endcan
                            @can('settings.manage-system')
                                <x-erp.nav-link :href="route('settings.system')" :active="request()->routeIs('settings.system')" nested soon>System Settings</x-erp.nav-link>
                            @endcan
                        </x-erp.nav-group>
                    @endcanany
                </nav>
            </aside>

            {{-- Mobile overlay --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-black/40 lg:hidden"
            ></div>

            {{-- Main column --}}
            <div class="flex-1 flex flex-col min-w-0">
                <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700" aria-label="Toggle sidebar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'ERP' }}</h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('home') }}" class="hidden sm:inline text-sm text-gray-500 hover:text-brand" target="_blank" rel="noopener">
                            View public site &#8599;
                        </a>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                <span class="h-8 w-8 rounded-full bg-brand text-white flex items-center justify-center text-xs font-semibold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden sm:block text-left leading-tight">
                                    {{ auth()->user()->name }}
                                    <span class="block text-xs text-gray-400 font-normal">{{ auth()->user()->getRoleNames()->join(', ') ?: 'No role' }}</span>
                                </span>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black/5 py-1"
                            >
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
