<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => fake()->unique()->date('Y-m'),
            'basic_salary' => 8_000_000,
            'allowances' => 1_000_000,
            'deductions' => 500_000,
            'net_salary' => 8_500_000,
            'status' => Payroll::DRAFT,
        ];
    }
}
