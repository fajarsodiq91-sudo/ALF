<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::orderBy('type')->orderBy('name')->get();

        return view('erp.finance.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('finance.categories')
            ->with('status', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('finance.manage');

        return view('erp.finance.categories.edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('finance.categories')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('finance.manage');

        $hasTransactions = $category->incomeTransactions()->exists()
            || $category->expenseTransactions()->exists();

        if ($hasTransactions) {
            return redirect()
                ->route('finance.categories')
                ->with('error', 'This category has transactions recorded against it and cannot be deleted. Deactivate it instead.');
        }

        $category->delete();

        return redirect()
            ->route('finance.categories')
            ->with('status', 'Category deleted successfully.');
    }
}
