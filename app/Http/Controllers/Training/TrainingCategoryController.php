<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\SaveTrainingCategoryRequest;
use App\Models\TrainingCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingCategoryController extends Controller
{
    public function index(): View
    {
        return view('erp.training.categories.index', [
            'categories' => TrainingCategory::withCount('programs')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('training.manage');

        return view('erp.training.categories.create');
    }

    public function store(SaveTrainingCategoryRequest $request): RedirectResponse
    {
        TrainingCategory::create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('training.categories.index')->with('status', 'Category created successfully.');
    }

    public function edit(TrainingCategory $category): View
    {
        $this->authorize('training.manage');

        return view('erp.training.categories.edit', ['category' => $category]);
    }

    public function update(SaveTrainingCategoryRequest $request, TrainingCategory $category): RedirectResponse
    {
        $category->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('training.categories.index')->with('status', 'Category updated successfully.');
    }

    public function destroy(TrainingCategory $category): RedirectResponse
    {
        $this->authorize('training.manage');

        if ($category->programs()->exists()) {
            return redirect()->route('training.categories.index')
                ->with('error', 'This category has programs assigned to it and cannot be deleted.');
        }

        $category->delete();

        return redirect()->route('training.categories.index')->with('status', 'Category deleted successfully.');
    }
}
