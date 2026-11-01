<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\TrainingSessionPayment;
use App\Models\User;
use App\Services\PaymentInvoices;
use App\Services\SessionPaymentPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-11-01 10:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @return list<array<string, mixed>> */
    private function programsPayload(TrainingProgram $program, int $count = 1, array $dates = ['2026-12-01', '2026-12-03']): array
    {
        return collect($dates)->take($count)->map(fn ($date) => [
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'fee' => 1000000, 'payment_plan' => 'full',
            'meetings' => [['meeting_date' => $date, 'start_time' => '20:00', 'end_time' => '21:30']],
        ])->all();
    }

    private function sessionWithFee(float $fee = 1000000): TrainingSession
    {
        $session = TrainingSession::factory()->create(['customer_id' => Customer::factory()->create(['email' => 'customer@example.com'])->id, 'fee' => $fee, 'payment_plan' => 'full']);
        TrainingSessionMeeting::factory()->count(4)->create(['training_session_id' => $session->id]);
        SessionPaymentPlan::generate($session->refresh());
        SessionPaymentPlan::addRegistrationFee($session);

        return $session->refresh();
    }

    public function test_approval_adds_the_registration_fee_once_to_the_first_program_only(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->userWithRole('Finance'))->post(route('sales.approve', $customer), ['programs' => $this->programsPayload($program, 2)])
            ->assertRedirect(route('sales.show', $customer));

        $fees = TrainingSessionPayment::where('is_registration_fee', true)->get();
        $this->assertCount(1, $fees);
        $this->assertSame('250000.00', $fees->first()->amount);
        $this->assertSame('Registration fee', $fees->first()->label);
        $this->assertSame('Upon registration', $fees->first()->dueLabel());
        $this->assertSame(TrainingSession::orderBy('id')->first()->id, $fees->first()->training_session_id);
        $this->assertSame(3, TrainingSessionPayment::count());
        $this->assertSame(['Registration fee', 'Full payment'], TrainingSession::orderBy('id')->first()->payments->pluck('label')->all());
    }

    public function test_no_registration_fee_when_none_is_set(): void
    {
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->userWithRole('Finance'))->post(route('sales.approve', $customer), ['programs' => $this->programsPayload($program)]);

        $this->assertSame(0, TrainingSessionPayment::where('is_registration_fee', true)->count());
        $this->assertSame(1, TrainingSessionPayment::count());
    }

    public function test_a_customer_who_was_already_charged_is_not_charged_again(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $first = $this->sessionWithFee();

        $second = TrainingSession::factory()->create(['customer_id' => $first->customer_id, 'fee' => 0]);
        SessionPaymentPlan::addRegistrationFee($second);

        $this->assertSame(1, TrainingSessionPayment::where('is_registration_fee', true)->count());
        $this->assertSame(0, $second->payments()->count());
    }

    public function test_regenerating_the_program_payments_keeps_the_registration_fee(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $session = $this->sessionWithFee();

        $session->update(['fee' => 2000000]);
        SessionPaymentPlan::generate($session);

        $this->assertEqualsCanonicalizing(['250000.00', '2000000.00'], $session->payments()->pluck('amount')->all());
    }

    public function test_a_paid_registration_fee_does_not_lock_the_program_fee(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $session = $this->sessionWithFee();
        $finance = $this->userWithRole('Finance');

        $this->actingAs($finance)->post(route('training.payments.pay', $session->payments()->where('is_registration_fee', true)->first()), ['paid_date' => '2026-11-01', 'payment_method' => 'Cash'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($session->payments()->where('is_registration_fee', true)->first()->isPaid());

        $this->actingAs($finance)->put(route('training.update', $session), [
            'training_program_id' => $session->training_program_id, 'customer_id' => $session->customer_id,
            'start_date' => '2026-12-01', 'end_date' => '2026-12-22', 'delivery_mode' => 'onsite',
            'participants_count' => 1, 'fee' => 1500000, 'payment_plan' => 'full', 'status' => 'planned',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['250000.00', '1500000.00'], $session->payments()->pluck('amount')->all());
        $this->assertTrue($session->payments()->where('is_registration_fee', true)->first()->isPaid());
    }

    public function test_a_program_can_override_the_default_fee_or_make_it_free(): void
    {
        Setting::put(['registration_fee' => '250000']);

        $this->assertSame(250000.0, SessionPaymentPlan::registrationFee(TrainingProgram::factory()->create(['registration_fee' => null])));
        $this->assertSame(400000.0, SessionPaymentPlan::registrationFee(TrainingProgram::factory()->create(['registration_fee' => 400000])));
        $this->assertSame(0.0, SessionPaymentPlan::registrationFee(TrainingProgram::factory()->create(['registration_fee' => 0])));
    }

    public function test_the_first_program_decides_the_fee_and_a_free_first_program_means_no_fee(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $free = TrainingProgram::factory()->create(['registration_fee' => 0]);
        $paid = TrainingProgram::factory()->create(['registration_fee' => 400000]);
        $finance = $this->userWithRole('Finance');

        $freeFirst = Customer::factory()->pendingApproval()->create();
        $this->actingAs($finance)->post(route('sales.approve', $freeFirst), ['programs' => [...$this->programsPayload($free, 1, ['2026-12-01']), ...$this->programsPayload($paid, 1, ['2026-12-03'])]]);
        $this->assertSame(2, $freeFirst->sessions()->count());
        $this->assertSame(0, TrainingSessionPayment::where('is_registration_fee', true)->count());

        $paidFirst = Customer::factory()->pendingApproval()->create();
        $this->actingAs($finance)->post(route('sales.approve', $paidFirst), ['programs' => [...$this->programsPayload($paid, 1, ['2026-12-08']), ...$this->programsPayload($free, 1, ['2026-12-10'])]]);
        $this->assertSame(['400000.00'], TrainingSessionPayment::where('is_registration_fee', true)->pluck('amount')->all());
    }

    public function test_the_program_form_saves_the_fee_and_empty_means_the_default(): void
    {
        $admin = $this->userWithRole('Super Admin');
        $program = TrainingProgram::factory()->create();
        $payload = ['name' => $program->name, 'program_type' => $program->program_type, 'duration_days' => 4, 'standard_price' => 1000000, 'is_active' => 1, 'training_category_id' => $program->training_category_id];

        $this->actingAs($admin)->put(route('training.programs.update', $program), [...$payload, 'registration_fee' => '0'])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $program->fresh()->registration_fee);

        $this->actingAs($admin)->put(route('training.programs.update', $program), [...$payload, 'registration_fee' => ''])->assertSessionHasNoErrors();
        $this->assertNull($program->fresh()->registration_fee);
    }

    public function test_the_program_waits_for_the_registration_fee(): void
    {
        Setting::put(['registration_fee' => '250000']);
        $session = $this->sessionWithFee();
        $finance = $this->userWithRole('Finance');
        $admin = $this->userWithRole('Super Admin');
        [$fee, $program] = [$session->payments()->where('is_registration_fee', true)->first(), $session->payments()->where('is_registration_fee', false)->first()];

        // Not due, not invoiced, cannot be recorded, and no meeting can be marked done.
        $this->assertFalse($program->fresh()->isDue());
        $this->assertSame(1, PaymentInvoices::sendDue($session)); // only the registration fee invoice goes out
        $this->actingAs($finance)->post(route('training.payments.pay', $program), ['paid_date' => '2026-11-01', 'payment_method' => 'Cash'])
            ->assertSessionHas('error');
        $this->assertFalse($program->fresh()->isPaid());
        $this->actingAs($admin)->patch(route('training.meetings.toggle', $session->meetings()->first()))->assertSessionHas('error');
        $this->assertFalse($session->meetings()->first()->is_completed);

        // Once the registration fee is paid, the program fee is invoiced and the program can run.
        $this->actingAs($finance)->post(route('training.payments.pay', $fee), ['paid_date' => '2026-11-01', 'payment_method' => 'Cash']);
        $this->assertTrue($program->fresh()->isDue());
        $this->assertNotNull($program->fresh()->invoice_sent_at);
        $this->actingAs($admin)->patch(route('training.meetings.toggle', $session->meetings()->first()))->assertSessionHasNoErrors();
        $this->assertTrue($session->meetings()->first()->is_completed);
    }

    public function test_a_session_without_a_registration_fee_is_not_held_back(): void
    {
        $session = $this->sessionWithFee();

        $this->assertFalse($session->hasUnpaidRegistrationFee());
        $this->assertTrue($session->payments()->first()->isDue());
    }

    public function test_the_fee_is_set_in_system_settings(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->put(route('settings.system.update'), ['company_name' => 'ALF', 'registration_fee' => '250000'])
            ->assertSessionHasNoErrors();
        $this->assertSame(250000.0, SessionPaymentPlan::registrationFee());

        $this->actingAs($admin)->put(route('settings.system.update'), ['company_name' => 'ALF', 'registration_fee' => '-5'])
            ->assertSessionHasErrors('registration_fee');

        $this->actingAs($admin)->put(route('settings.system.update'), ['company_name' => 'ALF', 'registration_fee' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame(0.0, SessionPaymentPlan::registrationFee());
    }
}
