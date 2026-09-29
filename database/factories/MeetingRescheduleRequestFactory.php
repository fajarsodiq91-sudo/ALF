<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MeetingRescheduleRequest;
use App\Models\TrainingSessionMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingRescheduleRequest>
 */
class MeetingRescheduleRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_session_meeting_id' => TrainingSessionMeeting::factory(),
            'customer_id' => Customer::factory(),
            'requested_date' => '2026-10-12',
            'requested_start_time' => '09:00',
            'requested_end_time' => '12:00',
            'reason' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }
}
