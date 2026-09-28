<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_code' => 'AST-'.fake()->unique()->numerify('#####'),
            'name' => fake()->randomElement(['Laptop', 'Monitor', 'Office Desk', 'Printer', 'Projector']).' '.fake()->numerify('##'),
            'category' => fake()->randomElement(array_keys(Asset::CATEGORIES)),
            'status' => 'active',
            'purchase_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'purchase_cost' => fake()->randomFloat(2, 500_000, 30_000_000),
            'location' => fake()->optional()->word(),
            'assigned_to' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
