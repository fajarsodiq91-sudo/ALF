<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Cash', 'Bank BCA', 'Bank Mandiri', 'E-Wallet']).' '.fake()->unique()->numerify('##'),
            'account_type' => fake()->randomElement(['cash', 'bank', 'e-wallet', 'other']),
            'account_number' => fake()->optional()->numerify('##########'),
            'opening_balance' => fake()->randomFloat(2, 0, 50_000_000),
            'is_active' => true,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
