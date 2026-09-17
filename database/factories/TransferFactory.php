<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transfer_number' => 'TRF-'.fake()->unique()->numerify('######-####'),
            'transfer_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'from_account_id' => Account::factory(),
            'to_account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 100_000, 10_000_000),
            'description' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
