<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanRepaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repayment_number' => 'LR-'.fake()->unique()->numerify('######-####'),
            'loan_id' => Loan::factory(),
            'repayment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 100_000, 500_000),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
