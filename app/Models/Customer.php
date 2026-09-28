<?php

namespace App\Models;

use App\Services\CustomerCodeGenerator;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'customer_type', 'contact_person', 'email', 'phone',
    'city', 'address', 'is_active', 'notes', 'photo_path',
])]
class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    public const REGISTRATION_COMPLETE = 'complete';

    public const REGISTRATION_AWAITING = 'awaiting_customer';

    /** The customer submitted their own details and the company still has to approve them. */
    public const REGISTRATION_PENDING_APPROVAL = 'pending_approval';

    public const REGISTRATION_REJECTED = 'rejected';

    public const TOKEN_VALID_DAYS = 7;

    protected $hidden = ['password', 'remember_token'];

    /**
     * The permanent customer ID is assigned once and is never mass-assignable.
     * Customers still waiting to fill in their own details get it when they finish.
     */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (! in_array($customer->registration_status, [self::REGISTRATION_AWAITING, self::REGISTRATION_PENDING_APPROVAL], true)) {
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
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /** Customers whose registration is finished (hides invitations still waiting for the customer). */
    public function scopeRegistered(Builder $query): void
    {
        $query->where('registration_status', self::REGISTRATION_COMPLETE);
    }

    public function isPendingApproval(): bool
    {
        return $this->registration_status === self::REGISTRATION_PENDING_APPROVAL;
    }

    public function isRejected(): bool
    {
        return $this->registration_status === self::REGISTRATION_REJECTED;
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CustomerProject::class);
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
     * The customer submitted their own details through the invitation link. They now wait for
     * the company's approval; the permanent ID is only assigned when that happens.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitRegistration(array $data): void
    {
        $this->fill($data);
        $this->registration_status = self::REGISTRATION_PENDING_APPROVAL;
        $this->registration_token = null;
        $this->registration_token_expires_at = null;
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * An admin filling in an invitation themselves: completes it on the spot, no portal access.
     * Assigns the permanent ID and closes the invitation link.
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
