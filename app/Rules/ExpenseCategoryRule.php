<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExpenseCategoryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $category = Category::find($value);

        if (!$category || $category->type !== 'expense') {
            $fail('The ' . $attribute . ' must be an expense category.');
        }
    }
}
