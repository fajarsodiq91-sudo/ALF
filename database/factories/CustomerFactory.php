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
            'customer_type' => fake()->randomElement(['company', 'individual', 'government']),
            'contact_person' => fake()->optional()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('08##########'),
            'city' => fake()->optional()->city(),
            'address' => fake()->optional()->address(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /** A customer created by an admin who still has to fill in their own details. */
    public function awaitingCustomer(): static
    {
        return $this->state(fn () => [
            'name' => null,
            'contact_person' => null,
            'email' => null,
            'phone' => null,
            'city' => null,
            'address' => null,
            'notes' => null,
            'registration_status' => Customer::REGISTRATION_AWAITING,
        ]);
    }
}
