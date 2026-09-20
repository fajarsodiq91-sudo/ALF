<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_number' => 'LN-'.fake()->unique()->numerify('######-####'),
            'direction' => Loan::OWNER_BORROWS,
            'loan_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'account_id' => Account::factory(),
            'party_name' => fake()->name(),
            'amount' => fake()->randomFloat(2, 1_000_000, 20_000_000),
            'description' => fake()->optional()->sentence(),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function companyBorrows(): static
    {
        return $this->state(fn () => ['direction' => Loan::COMPANY_BORROWS]);
    }
}
