<?php

namespace App\Models;

use App\Services\CustomerCodeGenerator;
use App\Services\SessionPaymentPlan;
use Carbon\Carbon;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'customer_type', 'email', 'phone',
    'city', 'address', 'is_active', 'notes', 'photo_path', 'requested_programs', 'company_customer_id', 'terms_accepted_at',
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
            'requested_programs' => 'array',
            'must_change_password' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
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

    /** The company this person belongs to, when they joined a corporate session as a participant. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(self::class, 'company_customer_id');
    }

    public function isParticipant(): bool
    {
        return $this->company_customer_id !== null;
    }

    /** Sessions of a company the person joined as a participant. */
    public function participatingSessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class, 'training_session_participants')->withTimestamps();
    }

    /** Every session the person can see in the portal: their own and the ones they participate in. */
    public function portalSessions(): Builder
    {
        return TrainingSession::query()->where(fn ($query) => $query
            ->where('customer_id', $this->id)
            ->orWhereIn('id', $this->participatingSessions()->select('training_sessions.id')));
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CustomerProject::class);
    }

    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(MeetingRescheduleRequest::class);
    }

    /**
     * Meeting ids of the customer's own (non-participant) sessions they may still request a reschedule for.
     *
     * @return list<int>
     */
    public function reschedulableMeetingIds(): array
    {
        return TrainingSessionMeeting::query()
            ->whereHas('session', fn (Builder $query) => $query->where('customer_id', $this->id)->whereNotIn('status', ['completed', 'cancelled']))
            ->where('is_completed', false)
            ->pluck('id')
            ->all();
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
        $this->status_token = Str::random(40);
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

    /** Finds a registration by the permanent status link sent to the customer after they submitted. */
    public static function findByStatusToken(string $token): ?self
    {
        return self::where('status_token', $token)->first();
    }

    /** The public, login-free link to this customer's portfolio (shown on their certificates' QR code). Created the first time it is needed. */
    public function portfolioToken(): string
    {
        if ($this->portfolio_token === null) {
            $this->forceFill(['portfolio_token' => Str::random(40)])->save();
        }

        return $this->portfolio_token;
    }

    /** Finds a customer by their public portfolio link token. */
    public static function findByPortfolioToken(string $token): ?self
    {
        return self::where('portfolio_token', $token)->first();
    }

    /**
     * The programs and preferred dates the customer picked while registering, with the
     * program records resolved. Programs that no longer exist are dropped.
     *
     * @return list<array{program: TrainingProgram, group_size: int, per_person: float, plan: string, price: float, payments: list<array{label: string, amount: float, when: string}>, meetings: list<array{date: Carbon, start: ?string, end: ?string, label: string}>}>
     */
    public function requestedProgramSummaries(): array
    {
        $requested = collect($this->requested_programs ?? []);
        $programs = TrainingProgram::whereIn('id', $requested->pluck('training_program_id'))->get()->keyBy('id');

        return $requested
            ->filter(fn ($entry) => $programs->has($entry['training_program_id']))
            ->map(function ($entry) use ($programs) {
                $program = $programs[$entry['training_program_id']];
                $groupSize = min(max(1, (int) ($entry['group_size'] ?? 1)), $program->maxGroupSize());
                $price = $program->groupTotal($groupSize);

                return [
                    'program' => $program,
                    'group_size' => $groupSize,
                    'per_person' => $program->pricePerPerson($groupSize),
                    'plan' => SessionPaymentPlan::effective($entry['payment_plan'] ?? SessionPaymentPlan::FULL, count($entry['meetings'] ?? []), $program->session_minutes),
                    'price' => $price,
                    'payments' => SessionPaymentPlan::preview($price, $entry['payment_plan'] ?? SessionPaymentPlan::FULL, count($entry['meetings'] ?? []) ?: null, $program->session_minutes),
                    'meetings' => collect($entry['meetings'] ?? [])->map(function ($meeting) {
                        $date = Carbon::parse($meeting['meeting_date']);
                        $time = ! empty($meeting['start_time']) ? ', '.substr($meeting['start_time'], 0, 5).(! empty($meeting['end_time']) ? ' – '.substr($meeting['end_time'], 0, 5) : '') : '';

                        return [
                            'date' => $date,
                            'start' => $meeting['start_time'] ?? null,
                            'end' => $meeting['end_time'] ?? null,
                            'label' => $date->format('D, d M Y').$time,
                        ];
                    })->all(),
                ];
            })
            ->values()
            ->all();
    }
}
