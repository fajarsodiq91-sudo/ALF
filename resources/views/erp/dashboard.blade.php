@php
    use App\Models\Customer;
    use App\Models\LeaveRequest;
    use App\Models\Payroll;
    use App\Models\Project;
    use App\Models\ProjectTask;
    use App\Models\TrainingSessionPayment;

    $followUps = [];

    if (auth()->user()->can('sales.view')) {
        $count = Customer::where('registration_status', Customer::REGISTRATION_PENDING_APPROVAL)->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Customer registrations',
                'sub' => 'awaiting approval',
                'count' => $count,
                'route' => route('sales.index', ['status' => Customer::REGISTRATION_PENDING_APPROVAL]),
                'icon' => 'user',
            ];
        }
    }

    if (auth()->user()->can('training.view')) {
        $count = TrainingSessionPayment::whereNull('income_transaction_id')->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Training payments',
                'sub' => 'awaiting confirmation',
                'count' => $count,
                'route' => route('training.index', ['payment' => 'awaiting']),
                'icon' => 'cash',
            ];
        }
    }

    if (auth()->user()->can('hr.view')) {
        $count = LeaveRequest::where('status', 'pending')->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Leave requests',
                'sub' => 'awaiting review',
                'count' => $count,
                'route' => route('hr.leaves.index', ['status' => 'pending']),
                'icon' => 'calendar',
            ];
        }
    }

    if (auth()->user()->can('hr.payroll')) {
        $count = Payroll::where('status', Payroll::DRAFT)->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Payroll runs',
                'sub' => 'still in draft',
                'count' => $count,
                'route' => route('hr.payroll.index', ['status' => Payroll::DRAFT]),
                'icon' => 'payroll',
            ];
        }
    }

    if (auth()->user()->can('projects.view')) {
        $count = Project::where('end_date', '<', now())->whereNotIn('status', ['completed', 'cancelled'])->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Projects',
                'sub' => 'overdue',
                'count' => $count,
                'route' => route('projects.index', ['overdue' => 1]),
                'icon' => 'project',
            ];
        }

        $count = ProjectTask::where('due_date', '<', now())->where('status', '!=', 'done')->count();
        if ($count > 0) {
            $followUps[] = [
                'label' => 'Project tasks',
                'sub' => 'overdue',
                'count' => $count,
                'route' => route('projects.index', ['task_overdue' => 1]),
                'icon' => 'task',
            ];
        }
    }

    $icons = [
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'cash' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'payroll' => 'M9 7h6m-6 4h6m-6 4h3m-9 4h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z',
        'project' => 'M9 3v2m6-2v2M4 8h16M5 8h14a1 1 0 011 1v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9a1 1 0 011-1z',
        'task' => 'M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
@endphp

<x-layouts.erp title="Dashboard">
    <div class="max-w-5xl space-y-6">
        @if (count($followUps) > 0)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Needs your attention</h3>
                <p class="mb-4 text-xs text-gray-500">Pending approvals, confirmations, and other tasks waiting on you.</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach ($followUps as $item)
                        <a href="{{ $item['route'] }}" class="flex items-center gap-2.5 rounded-lg border border-gray-200 p-3 hover:border-brand/40 hover:bg-brand-50/50 transition-colors group">
                            <span class="h-9 w-9 shrink-0 rounded-full bg-brand-50 text-brand flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-lg font-semibold leading-tight text-gray-800">{{ $item['count'] }}</span>
                                <span class="block text-xs text-gray-500 truncate group-hover:text-gray-700">{{ $item['label'] }} {{ $item['sub'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-800">Calendar &amp; operating hours</h3>
            <p class="mb-3 text-xs text-gray-500">Open slots and the ones already booked by customers or blocked by the company.</p>
            @include('erp.partials.availability-calendar')
        </div>
    </div>
</x-layouts.erp>
