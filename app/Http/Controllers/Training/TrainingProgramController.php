<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingProgramRequest;
use App\Models\TrainingCategory;
use App\Models\TrainingProgram;
use App\Models\TrainingProgramImage;
use App\Services\ImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingProgramController extends Controller
{
    public function index(): View
    {
        return view('erp.training.programs.index', [
            'programs' => TrainingProgram::with(['category', 'images'])->withCount('sessions')->orderBy('name')->get(),
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
        $program = TrainingProgram::create([
            ...$request->safe()->except(['images', 'remove_images']),
            'is_active' => $request->boolean('is_active'),
            'is_corporate' => $request->boolean('is_corporate'),
        ]);

        $this->addImages($program, $request);

        return redirect()->route('training.programs.index')->with('status', 'Program created successfully.');
    }

    public function edit(TrainingProgram $program): View
    {
        $this->authorize('training.manage');

        $categories = TrainingCategory::where('is_active', true)
            ->orWhere('id', $program->training_category_id)
            ->orderBy('name')->get();

        return view('erp.training.programs.edit', ['program' => $program->load('images'), 'categories' => $categories]);
    }

    public function update(SaveTrainingProgramRequest $request, TrainingProgram $program): RedirectResponse
    {
        $program->update([
            ...$request->safe()->except(['images', 'remove_images']),
            'is_active' => $request->boolean('is_active'),
            'is_corporate' => $request->boolean('is_corporate'),
        ]);

        $program->images()->whereIn('id', $request->input('remove_images', []))->get()->each->delete();
        $this->addImages($program, $request);

        return redirect()->route('training.programs.index')->with('status', 'Program updated successfully.');
    }

    public function destroy(TrainingProgram $program): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($program->sessions()->exists()) {
            return redirect()->route('training.programs.index')
                ->with('error', 'This program has sessions and cannot be deleted. Deactivate it instead.');
        }

        $program->images->each->delete();
        $program->delete();

        return redirect()->route('training.programs.index')->with('status', 'Program deleted successfully.');
    }

    /** Stores newly uploaded illustration photos, appended after whatever the program already has. */
    private function addImages(TrainingProgram $program, SaveTrainingProgramRequest $request): void
    {
        $next = $program->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $i => $image) {
            TrainingProgramImage::create([
                'training_program_id' => $program->id,
                'path' => ImageCompressor::store($image, 'training-program-images', 'public'),
                'sort_order' => $next + $i,
            ]);
        }
    }
}
