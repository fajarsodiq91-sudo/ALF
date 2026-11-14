<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory()->withPortalAccess('secret-pass'),
            'status' => ServiceOrder::BRIEF,
            'title' => 'Sales dashboard',
            'purpose' => 'Track monthly sales per branch.',
            'kpis' => 'Revenue, margin, top products',
            'data_sources' => 'Excel export from our POS',
            'needs_refresh' => false,
        ];
    }

    /** A quote has been sent, with a single line item. */
    public function quoted(float $total = 2000000): static
    {
        return $this->state(fn () => [
            'status' => ServiceOrder::QUOTED,
            'quote_items' => [['label' => 'Dashboard', 'amount' => $total]],
            'quote_total' => $total,
            'dp_percentage' => 50,
            'estimated_days' => 7,
            'included_revisions' => 2,
            'quoted_at' => now(),
        ]);
    }
}
