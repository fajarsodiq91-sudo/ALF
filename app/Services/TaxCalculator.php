<?php

namespace App\Services;

use App\Models\Tax;

class TaxCalculator
{
    /**
     * Returns the fields to persist on a transaction given the pre-tax amount.
     * VAT is added to the amount; withholding tax is deducted from it.
     *
     * @return array{subtotal: string, tax_id: ?int, tax_rate: ?string, tax_amount: string, amount: string}
     */
    public static function apply(float|string $subtotal, ?int $taxId): array
    {
        $subtotal = round((float) $subtotal, 2);
        $tax = $taxId ? Tax::find($taxId) : null;

        if (! $tax) {
            return [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'tax_id' => null,
                'tax_rate' => null,
                'tax_amount' => '0.00',
                'amount' => number_format($subtotal, 2, '.', ''),
            ];
        }

        $taxAmount = round($subtotal * (float) $tax->rate / 100, 2);
        $total = $tax->type === Tax::TYPE_VAT ? $subtotal + $taxAmount : $subtotal - $taxAmount;

        return [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_id' => $tax->id,
            'tax_rate' => (string) $tax->rate,
            'tax_amount' => number_format($taxAmount, 2, '.', ''),
            'amount' => number_format($total, 2, '.', ''),
        ];
    }
}
