<?php

namespace Database\Factories;

use App\Models\TrainingSession;
use App\Models\TrainingSessionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSessionPayment>
 */
class TrainingSessionPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'label' => 'Full payment',
            'percentage' => 100,
            'amount' => 5_000_000,
            'due_meeting_number' => null,
        ];
    }
}
