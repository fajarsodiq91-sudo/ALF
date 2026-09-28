<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'attendance_date' => fake()->unique()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'status' => 'present',
            'check_in' => '08:00',
            'check_out' => '17:00',
        ];
    }
}
