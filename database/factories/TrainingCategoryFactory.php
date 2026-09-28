<?php

namespace Database\Factories;

use App\Models\TrainingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingCategory>
 */
class TrainingCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Data Analyst', 'Operating Komputer', 'Digital Marketing', 'Project Management', 'Bahasa Asing']).' '.fake()->unique()->numerify('##'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
