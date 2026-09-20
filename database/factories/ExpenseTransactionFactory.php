<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaction_number' => 'EXP-'.fake()->unique()->numerify('######-####'),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'account_id' => Account::factory(),
            'category_id' => Category::factory()->expense(),
            'payee' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'subtotal' => fn (array $a) => $a['amount'],
            'tax_id' => null,
            'tax_rate' => null,
            'tax_amount' => 0,
            'amount' => fake()->randomFloat(2, 100_000, 20_000_000),
            'payment_method' => fake()->randomElement(['Cash', 'Bank Transfer', 'QRIS']),
            'attachment_path' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
