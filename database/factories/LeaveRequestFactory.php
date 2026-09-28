<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type' => LeaveRequest::ANNUAL,
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-04',
            'days' => 3,
            'reason' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }
}
