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
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
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
            'email' => null,
            'phone' => null,
            'city' => null,
            'address' => null,
            'notes' => null,
            'registration_status' => Customer::REGISTRATION_AWAITING,
        ]);
    }

    /** A customer who filled in their own details and waits for the company's approval. */
    public function pendingApproval(): static
    {
        return $this->state(fn () => [
            'email' => fake()->unique()->safeEmail(),
            'registration_status' => Customer::REGISTRATION_PENDING_APPROVAL,
            'submitted_at' => now(),
        ]);
    }

    /** An approved customer who can use the portal: password equals the ID, as the approval sets it. */
    public function withPortalAccess(?string $password = null): static
    {
        return $this->afterCreating(function (Customer $customer) use ($password) {
            $customer->forceFill([
                'password' => $password ?? $customer->customer_code,
                'must_change_password' => $password === null,
                'approved_at' => now(),
            ])->save();
        });
    }
}
