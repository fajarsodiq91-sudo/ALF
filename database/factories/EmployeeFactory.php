<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => 'EMP-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('08##########'),
            'position' => fake()->randomElement(['Developer', 'Designer', 'Project Manager', 'Accountant', 'Sales Executive']),
            'department' => fake()->optional()->randomElement(['Engineering', 'Finance', 'Sales', 'Operations']),
            'employment_type' => fake()->randomElement(array_keys(Employee::EMPLOYMENT_TYPES)),
            'status' => 'active',
            'join_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'annual_leave_quota' => 12,
            'address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
