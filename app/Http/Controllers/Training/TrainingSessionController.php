<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingSessionRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
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
        TrainingSession::create($request->validated());

        return redirect()->route('training.index')->with('status', 'Training session created successfully.');
    }

    public function edit(TrainingSession $session): View
    {
        $this->authorize('training.manage');

        return view('erp.training.sessions.edit', [...$this->formData(), 'session' => $session]);
    }

    public function update(SaveTrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $session->update($request->validated());

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
