<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerProject>
 */
class CustomerProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'training_session_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'external_url' => fake()->url(),
            'in_portfolio' => false,
        ];
    }
}
