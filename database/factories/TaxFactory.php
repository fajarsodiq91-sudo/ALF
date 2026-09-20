<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => 'vat',
            'rate' => 11,
            'is_active' => true,
            'description' => null,
        ];
    }

    public function vat(float $rate = 11): static
    {
        return $this->state(fn () => ['type' => 'vat', 'rate' => $rate]);
    }

    public function withholding(float $rate = 2): static
    {
        return $this->state(fn () => ['type' => 'withholding', 'rate' => $rate]);
    }
}
