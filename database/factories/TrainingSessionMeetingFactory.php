<?php

namespace Database\Factories;

use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSessionMeeting>
 */
class TrainingSessionMeetingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'meeting_date' => '2026-10-05',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'location' => fake()->optional()->city(),
            'topic' => fake()->optional()->sentence(3),
            'is_completed' => false,
        ];
    }
}
