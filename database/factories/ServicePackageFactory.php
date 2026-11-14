<?php

namespace Database\Factories;

use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Excel Dashboard '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'features' => "1 data source\n1 dashboard page",
            'starting_price' => 1000000,
            'included_revisions' => 2,
            'estimated_days' => 7,
            'addons' => [['label' => 'Extra page', 'price' => 300000]],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
