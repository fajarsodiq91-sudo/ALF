<?php

namespace App\Models;

use App\Services\CustomerCodeGenerator;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'customer_type', 'contact_person', 'email', 'phone',
    'city', 'address', 'is_active', 'notes', 'photo_path',
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

    public const REGISTRATION_COMPLETE = 'complete';

    public const REGISTRATION_AWAITING = 'awaiting_customer';

    public const TOKEN_VALID_DAYS = 7;

    /**
     * The permanent customer ID is assigned once and is never mass-assignable.
     * Customers still waiting to fill in their own details get it when they finish.
     */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if ($customer->registration_status !== self::REGISTRATION_AWAITING) {
                $customer->customer_code ??= CustomerCodeGenerator::next(now());
            }
        });

        static::deleted(function (Customer $customer) {
            if ($customer->photo_path) {
                Storage::disk('public')->delete($customer->photo_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'registration_token_expires_at' => 'datetime',
        ];
    }

    /** Customers whose registration is finished (hides invitations still waiting for the customer). */
    public function scopeRegistered(Builder $query): void
    {
        $query->where('registration_status', self::REGISTRATION_COMPLETE);
    }

    public function isAwaitingCustomer(): bool
    {
        return $this->registration_status === self::REGISTRATION_AWAITING;
    }

    public function hasValidRegistrationToken(): bool
    {
        return $this->isAwaitingCustomer()
            && $this->registration_token !== null
            && $this->registration_token_expires_at?->isFuture() === true;
    }

    /** Creates a fresh one-time link token, invalidating any earlier one. */
    public function issueRegistrationToken(): string
    {
        $this->registration_token = Str::random(40);
        $this->registration_token_expires_at = now()->addDays(self::TOKEN_VALID_DAYS);
        $this->save();

        return $this->registration_token;
    }

    /** Finds an invitation by its link token, only while it is still usable. */
    public static function findByValidRegistrationToken(string $token): ?self
    {
        $customer = self::where('registration_token', $token)->first();

        return $customer?->hasValidRegistrationToken() ? $customer : null;
    }

    /**
     * Saves the details given by the customer (or an admin), assigns the permanent ID
     * and closes the invitation link.
     *
     * @param  array<string, mixed>  $data
     */
    public function completeRegistration(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->fill($data);
            $this->customer_code ??= CustomerCodeGenerator::next(now());
            $this->registration_status = self::REGISTRATION_COMPLETE;
            $this->registration_token = null;
            $this->registration_token_expires_at = null;
            $this->save();
        });
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }
}
