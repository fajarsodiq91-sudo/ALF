<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IncomeCategoryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $category = Category::find($value);

        if (!$category || $category->type !== 'income') {
            $fail('The ' . $attribute . ' must be an income category.');
        }
    }
}
