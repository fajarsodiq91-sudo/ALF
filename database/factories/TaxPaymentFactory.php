<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_number' => 'TAX-'.fake()->unique()->numerify('######-####'),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'account_id' => Account::factory(),
            'tax_type' => 'vat',
            'period' => now()->subMonth()->format('Y-m'),
            'amount' => fake()->randomFloat(2, 100_000, 5_000_000),
            'reference' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
