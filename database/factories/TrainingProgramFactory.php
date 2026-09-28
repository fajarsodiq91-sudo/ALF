<?php

namespace Database\Factories;

use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProgram>
 */
class TrainingProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Microsoft Excel', 'Microsoft Power BI', 'SQL', 'Python', 'AI for Business']).' '.fake()->unique()->numerify('##'),
            'description' => fake()->optional()->sentence(),
            'duration_days' => fake()->numberBetween(1, 5),
            'standard_price' => fake()->randomFloat(2, 2_000_000, 20_000_000),
            'is_active' => true,
        ];
    }
}
