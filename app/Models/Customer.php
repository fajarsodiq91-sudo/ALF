<?php

namespace App\Models;

use App\Services\CustomerCodeGenerator;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'customer_type', 'contact_person', 'email', 'phone',
    'city', 'address', 'is_active', 'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    public const TYPES = [
        'company' => 'Company',
        'individual' => 'Individual',
        'government' => 'Government / Institution',
    ];

    /** The customer ID is assigned once, on creation, and is never mass-assignable. */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->customer_code ??= CustomerCodeGenerator::next(now());
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
