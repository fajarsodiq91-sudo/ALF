<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PRJ-'.fake()->unique()->numerify('####'),
            'name' => fake()->randomElement(['Dashboard Analytics', 'ERP Implementation', 'Data Warehouse', 'AI Chatbot']).' '.fake()->numerify('##'),
            'customer_id' => Customer::factory(),
            'project_manager_id' => null,
            'start_date' => '2026-06-01',
            'end_date' => '2026-09-30',
            'contract_value' => fake()->randomFloat(2, 10_000_000, 500_000_000),
            'status' => 'planned',
            'description' => fake()->optional()->sentence(),
        ];
    }
}
