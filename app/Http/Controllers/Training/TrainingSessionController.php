<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingSessionRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Services\SessionPaymentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = TrainingSession::query()
            ->with(['program', 'customer', 'instructor'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('program_id'), fn ($query) => $query->where('training_program_id', $request->integer('program_id')))
            ->latest('start_date')
            ->get();

        return view('erp.training.sessions.index', [
            'sessions' => $sessions,
            'programs' => TrainingProgram::orderBy('name')->get(),
            'totalFee' => (float) $sessions->where('status', '!=', 'cancelled')->sum('fee'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('erp.training.sessions.create', $this->formData());
    }

    public function store(SaveTrainingSessionRequest $request): RedirectResponse
    {
        $session = TrainingSession::create([...$request->validated(), 'payment_plan' => $request->validated('payment_plan') ?? SessionPaymentPlan::FULL]);
        SessionPaymentPlan::generate($session);

        return redirect()->route('training.index')->with('status', 'Training session created successfully.');
    }

    public function edit(TrainingSession $session): View
    {
        $this->authorize('training.manage');

        return view('erp.training.sessions.edit', [...$this->formData(), 'session' => $session->load(['meetings', 'payments.incomeTransaction']), 'accounts' => Account::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(SaveTrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $data = [...$request->validated(), 'payment_plan' => $request->validated('payment_plan') ?? $session->payment_plan];
        $changesMoney = (float) $data['fee'] !== (float) $session->fee || $data['payment_plan'] !== $session->payment_plan;

        if ($changesMoney && $session->payments()->whereNotNull('income_transaction_id')->exists()) {
            return back()->withInput()->withErrors(['fee' => 'A payment for this session is already recorded in Finance. Cancel it before changing the fee or payment plan.']);
        }

        $session->update($data);

        // Sessions created before payment plans existed get their schedule the first time they are saved.
        if ($changesMoney || $session->payments()->doesntExist()) {
            SessionPaymentPlan::generate($session);
        }

        return redirect()->route('training.index')->with('status', 'Training session updated successfully.');
    }

    public function destroy(TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');

        $session->delete();

        return redirect()->route('training.index')->with('status', 'Training session deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => TrainingProgram::orderBy('name')->get(),
            'customers' => Customer::registered()->orderBy('name')->get(),
            'instructors' => Employee::where('status', '!=', 'resigned')->orderBy('name')->get(),
        ];
    }
}
