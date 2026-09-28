<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'customer_type' => fake()->randomElement(array_keys(Customer::TYPES)),
            'contact_person' => fake()->optional()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('08##########'),
            'city' => fake()->optional()->city(),
            'address' => fake()->optional()->address(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
