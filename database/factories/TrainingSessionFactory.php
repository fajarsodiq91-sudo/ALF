<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_program_id' => TrainingProgram::factory(),
            'customer_id' => Customer::factory(),
            'instructor_id' => null,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-05',
            'delivery_mode' => 'onsite',
            'location' => fake()->optional()->city(),
            'participants_count' => fake()->numberBetween(5, 30),
            'fee' => fake()->randomFloat(2, 3_000_000, 30_000_000),
            'status' => 'planned',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
