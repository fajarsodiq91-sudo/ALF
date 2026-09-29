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
            'program_type' => 'learning',
            'description' => fake()->optional()->sentence(),
            'duration_days' => 1,
            'standard_price' => fake()->randomFloat(2, 2_000_000, 20_000_000),
            'is_active' => true,
            'discount_type' => null,
            'discount_value' => null,
            'discount_expires_at' => null,
        ];
    }

    /** An active promo: a percentage or fixed cut, expiring some days from now. */
    public function withDiscount(string $type = TrainingProgram::DISCOUNT_PERCENTAGE, float $value = 10, int $expiresInDays = 7): static
    {
        return $this->state(fn () => [
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_expires_at' => now()->addDays($expiresInDays)->toDateString(),
        ]);
    }
}
