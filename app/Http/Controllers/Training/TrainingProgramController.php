<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingProgramRequest;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingProgramController extends Controller
{
    public function index(): View
    {
        return view('erp.training.programs.index', [
            'programs' => TrainingProgram::with('category')->withCount('sessions')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('erp.training.programs.create', [
            'categories' => TrainingCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(SaveTrainingProgramRequest $request): RedirectResponse
    {
        TrainingProgram::create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('training.programs.index')->with('status', 'Program created successfully.');
    }

    public function edit(TrainingProgram $program): View
    {
        $this->authorize('training.manage');

        $categories = TrainingCategory::where('is_active', true)
            ->orWhere('id', $program->training_category_id)
            ->orderBy('name')->get();

        return view('erp.training.programs.edit', ['program' => $program, 'categories' => $categories]);
    }

    public function update(SaveTrainingProgramRequest $request, TrainingProgram $program): RedirectResponse
    {
        $program->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('training.programs.index')->with('status', 'Program updated successfully.');
    }

    public function destroy(TrainingProgram $program): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($program->sessions()->exists()) {
            return redirect()->route('training.programs.index')
                ->with('error', 'This program has sessions and cannot be deleted. Deactivate it instead.');
        }

        $program->delete();

        return redirect()->route('training.programs.index')->with('status', 'Program deleted successfully.');
    }
}
