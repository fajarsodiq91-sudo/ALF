<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Mail\CustomerRegistrationReceived;
use App\Models\Account;
use App\Models\Customer;
use App\Models\IncomeTransaction;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use App\Services\SessionPaymentPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function finance(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    /** @return array<string, mixed> */
    private function sessionPayload(TrainingSession $session, array $overrides = []): array
    {
        return [
            'training_program_id' => $session->training_program_id, 'customer_id' => $session->customer_id,
            'start_date' => '2026-10-06', 'end_date' => '2026-10-27', 'delivery_mode' => 'onsite',
            'participants_count' => 1, 'fee' => (float) $session->fee, 'payment_plan' => $session->payment_plan, 'status' => 'planned',
            ...$overrides,
        ];
    }

    private function sessionWithMeetings(int $meetings, string $plan, float $fee = 8000000): TrainingSession
    {
        $session = TrainingSession::factory()->create(['fee' => $fee, 'payment_plan' => $plan]);
        for ($i = 0; $i < $meetings; $i++) {
            TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => Carbon::parse('2026-10-06')->addWeeks($i)->toDateString()]);
        }
        SessionPaymentPlan::generate($session->refresh());

        return $session->refresh();
    }

    public function test_middle_meeting_is_meeting_4_for_7_or_8_meetings(): void
    {
        $this->assertSame(4, SessionPaymentPlan::middleMeeting(8));
        $this->assertSame(4, SessionPaymentPlan::middleMeeting(7));
        $this->assertSame(3, SessionPaymentPlan::middleMeeting(5));
        $this->assertSame(1, SessionPaymentPlan::middleMeeting(1));
        $this->assertSame(1, SessionPaymentPlan::middleMeeting(0));
    }

    public function test_full_payment_plan_creates_one_upfront_payment(): void
    {
        $session = $this->sessionWithMeetings(4, 'full');

        $this->assertCount(1, $session->payments);
        $payment = $session->payments->first();
        $this->assertSame('8000000.00', $payment->amount);
        $this->assertNull($payment->due_meeting_number);
        $this->assertSame('Upon registration', $payment->dueLabel());
    }

    public function test_installment_plan_splits_50_50_with_the_second_half_at_the_middle_meeting(): void
    {
        $session = $this->sessionWithMeetings(8, 'installment');

        [$first, $second] = $session->payments->all();
        $this->assertSame('4000000.00', $first->amount);
        $this->assertSame('4000000.00', $second->amount);
        $this->assertNull($first->due_meeting_number);
        $this->assertSame(4, $second->due_meeting_number);
        $this->assertSame('At meeting 4', $second->dueLabel());
    }

    public function test_odd_amounts_never_lose_a_rupiah(): void
    {
        $session = $this->sessionWithMeetings(3, 'installment', 1000001);

        $this->assertSame(1000001.0, (float) $session->payments->sum('amount'));
        $this->assertSame(2, $session->payments->last()->due_meeting_number);
    }

    public function test_no_payments_for_a_free_session(): void
    {
        $this->assertCount(0, $this->sessionWithMeetings(2, 'installment', 0)->payments);
    }

    public function test_second_installment_follows_the_middle_meeting_as_meetings_change(): void
    {
        $session = $this->sessionWithMeetings(4, 'installment');
        $this->assertSame(2, $session->payments->last()->due_meeting_number);
        $finance = $this->finance();

        foreach (['2026-11-03', '2026-11-10', '2026-11-17', '2026-11-24'] as $date) {
            $this->actingAs($finance)->post(route('training.meetings.store', $session), ['meeting_date' => $date, 'start_time' => '20:00', 'end_time' => '21:30']);
        }
        $this->assertSame(4, $session->payments()->get()->last()->due_meeting_number); // 8 meetings

        $this->actingAs($finance)->delete(route('training.meetings.destroy', $session->meetings()->first()));
        $this->assertSame(4, $session->payments()->get()->last()->due_meeting_number); // 7 meetings
    }

    public function test_due_status_follows_the_meetings(): void
    {
        $session = $this->sessionWithMeetings(4, 'installment'); // 2nd half at meeting 2
        [$first, $second] = $session->payments()->get()->all();

        $this->assertTrue($first->isDue());
        $this->assertFalse($second->isDue()); // meeting 2 is on 2026-10-13, today is 2026-09-15

        $session->meetings()->skip(1)->first()->update(['is_completed' => true]);
        $this->assertTrue($second->fresh()->isDue());

        Carbon::setTestNow('2026-10-14');
        $session->meetings()->skip(1)->first()->update(['is_completed' => false]);
        $this->assertTrue($second->fresh()->isDue()); // the date has come even if not ticked yet
    }

    public function test_recording_a_payment_creates_income_in_finance(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $customer = Customer::factory()->create(['name' => 'PT Pembayar']);
        $session = $this->sessionWithMeetings(8, 'installment');
        $session->update(['customer_id' => $customer->id]);
        $payment = $session->payments()->first();

        $this->actingAs($this->finance())->post(route('training.payments.pay', $payment), [
            'account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Bank Transfer',
        ])->assertRedirect(route('training.edit', $session))->assertSessionHas('status');

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertSame('2026-09-16', $payment->paid_date->toDateString());

        $income = IncomeTransaction::findOrFail($payment->income_transaction_id);
        $this->assertSame('4000000.00', $income->amount);
        $this->assertSame($account->id, $income->account_id);
        $this->assertSame('PT Pembayar', $income->source);
        $this->assertSame('Training Revenue', $income->category->name);
        $this->assertSame('income', $income->category->type);
        $this->assertStringContainsString($customer->customer_code, $income->description);
        $this->assertStringContainsString('Down payment', $income->description);
        $this->assertSame(4000000.0, (float) $account->fresh()->currentBalance() - (float) $account->opening_balance);
    }

    public function test_a_payment_cannot_be_recorded_twice_and_can_be_cancelled(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $session = $this->sessionWithMeetings(4, 'full');
        $payment = $session->payments()->first();
        $finance = $this->finance();
        $data = ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash'];

        $this->actingAs($finance)->post(route('training.payments.pay', $payment), $data);
        $this->actingAs($finance)->post(route('training.payments.pay', $payment), $data)->assertSessionHas('error');
        $this->assertSame(1, IncomeTransaction::count());

        $this->actingAs($finance)->post(route('training.payments.cancel', $payment))->assertSessionHas('status');
        $this->assertFalse($payment->fresh()->isPaid());
        $this->assertNull($payment->fresh()->paid_date);
        $this->assertSame(0, IncomeTransaction::count());
        $this->actingAs($finance)->post(route('training.payments.cancel', $payment))->assertSessionHas('error');
    }

    public function test_deleting_the_income_in_finance_makes_the_payment_unpaid_again(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $payment = $this->sessionWithMeetings(2, 'full')->payments()->first();
        $this->actingAs($this->finance())->post(route('training.payments.pay', $payment), ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash']);

        IncomeTransaction::firstOrFail()->delete();

        $this->assertFalse($payment->fresh()->isPaid());
    }

    public function test_recording_needs_finance_permission_and_valid_input(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $inactive = Account::factory()->create(['is_active' => false]);
        $payment = $this->sessionWithMeetings(2, 'full')->payments()->first();
        $data = ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash'];

        $trainer = User::factory()->create();
        $trainer->givePermissionTo(['access-erp', 'training.view', 'training.manage']);
        $this->actingAs($trainer)->post(route('training.payments.pay', $payment), $data)->assertForbidden();
        $this->actingAs($trainer)->post(route('training.payments.cancel', $payment))->assertForbidden();

        $finance = $this->finance();
        $this->actingAs($finance)->post(route('training.payments.pay', $payment), [...$data, 'account_id' => $inactive->id])->assertSessionHasErrors('account_id');
        $this->actingAs($finance)->post(route('training.payments.pay', $payment), [...$data, 'payment_method' => 'Bitcoin'])->assertSessionHasErrors('payment_method');
        $this->actingAs($finance)->post(route('training.payments.pay', $payment), [...$data, 'paid_date' => ''])->assertSessionHasErrors('paid_date');
        $this->assertSame(0, IncomeTransaction::count());
    }

    public function test_session_edit_shows_payments_and_the_record_form_only_to_finance(): void
    {
        $session = $this->sessionWithMeetings(8, 'installment');
        Account::factory()->create(['name' => 'Bank BCA Utama', 'is_active' => true]);

        $this->actingAs($this->finance())->get(route('training.edit', $session))->assertOk()
            ->assertSee('Down payment (50%)')->assertSee('Final payment (50%)')->assertSee('At meeting 4')
            ->assertSee('Rp 4.000.000')->assertSee('Record payment')->assertSee('Bank BCA Utama')->assertSee('Not yet due');

        $trainer = User::factory()->create();
        $trainer->givePermissionTo(['access-erp', 'training.view', 'training.manage']);
        $this->actingAs($trainer)->get(route('training.edit', $session))->assertOk()->assertSee('Down payment (50%)')->assertDontSee('Record payment');
    }

    public function test_changing_fee_or_plan_rebuilds_unpaid_payments_but_not_after_one_is_recorded(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $session = $this->sessionWithMeetings(8, 'full', 6000000);
        $finance = $this->finance();

        $this->actingAs($finance)->put(route('training.update', $session), $this->sessionPayload($session, ['fee' => 10000000, 'payment_plan' => 'installment']))->assertRedirect(route('training.index'));
        $this->assertSame([5000000.0, 5000000.0], $session->payments()->get()->map(fn ($p) => (float) $p->amount)->all());

        $this->actingAs($finance)->post(route('training.payments.pay', $session->payments()->first()), ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash']);
        $session->refresh();

        $this->actingAs($finance)->put(route('training.update', $session), $this->sessionPayload($session, ['fee' => 12000000]))->assertSessionHasErrors('fee');
        $this->assertSame('10000000.00', $session->fresh()->fee);

        $this->actingAs($finance)->put(route('training.update', $session), $this->sessionPayload($session, ['notes' => 'hanya catatan']))->assertRedirect(route('training.index'));
        $this->assertCount(2, $session->payments()->get()); // untouched
    }

    public function test_older_sessions_without_a_schedule_get_one_when_saved(): void
    {
        $session = TrainingSession::factory()->create(['fee' => 4000000, 'payment_plan' => 'full']);
        $this->assertSame(0, $session->payments()->count());

        $this->actingAs($this->finance())->put(route('training.update', $session), $this->sessionPayload($session))->assertRedirect(route('training.index'));

        $this->assertSame(['4000000.00'], $session->payments()->get()->pluck('amount')->all());
    }

    public function test_creating_a_session_generates_its_payments(): void
    {
        $program = TrainingProgram::factory()->create();

        $this->actingAs($this->finance())->post(route('training.store'), [
            'training_program_id' => $program->id, 'start_date' => '2026-10-06', 'end_date' => '2026-10-06', 'delivery_mode' => 'online',
            'participants_count' => 1, 'fee' => 3000000, 'payment_plan' => 'installment', 'status' => 'planned',
        ])->assertRedirect(route('training.index'));

        $this->assertSame([1500000.0, 1500000.0], TrainingSession::firstOrFail()->payments()->get()->map(fn ($p) => (float) $p->amount)->all());
    }

    public function test_public_form_shows_prices_and_the_two_payment_choices(): void
    {
        TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'standard_price' => 7500000]);
        $this->actingAs($this->finance())->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $token = Customer::firstOrFail()->registration_token;

        $this->get(route('customer-registration.show', $token))->assertOk()
            ->assertSee('Program fee')->assertSee('How would you like to pay?')
            ->assertSee('Pay in full upfront')->assertSee('50% upfront, 50% at the middle meeting')
            ->assertSee('Total program fee')->assertSee('To pay when you register')
            ->assertSee('"1":7500000', false);
    }

    public function test_customer_choice_of_payment_plan_is_saved_shown_and_emailed(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'standard_price' => 7500000]);
        $this->actingAs($this->finance())->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $customer = Customer::firstOrFail();

        $this->post(route('customer-registration.store', $customer->registration_token), [
            'name' => 'PT Cicil', 'email' => 'cicil@pt.test', 'phone' => '0812',
            'programs' => [['training_program_id' => $program->id, 'payment_plan' => 'installment', 'meetings' => [
                ['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30'],
                ['meeting_date' => '2026-10-08', 'start_time' => '20:00', 'end_time' => '21:30'],
            ]]],
        ])->assertRedirect(route('customer-registration.done'));

        $this->assertSame('installment', $customer->fresh()->requested_programs[0]['payment_plan']);
        $this->get(route('customer-registration.done'))
            ->assertSee('Fee Rp 7.500.000')->assertSee('Down payment (50%): Rp 3.750.000')->assertSee('Final payment (50%): Rp 3.750.000 — At meeting 1');

        Mail::assertSent(CustomerRegistrationReceived::class, function (CustomerRegistrationReceived $mail) {
            $mail->assertSeeInHtml('Fee Rp 7.500.000');
            $mail->assertSeeInHtml('Down payment (50%) Rp 3.750.000');
            $mail->assertSeeInHtml('Upon registration');

            return true;
        });
    }

    public function test_an_unknown_payment_plan_is_rejected_and_full_is_the_default(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $this->actingAs($this->finance())->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $token = Customer::firstOrFail()->registration_token;
        $meeting = [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']];

        $this->post(route('customer-registration.store', $token), ['name' => 'PT X', 'email' => 'x@pt.test', 'phone' => '0812',
            'programs' => [['training_program_id' => $program->id, 'payment_plan' => 'bayar_nanti', 'meetings' => $meeting]]])->assertSessionHasErrors('programs.0.payment_plan');

        $this->post(route('customer-registration.store', $token), ['name' => 'PT X', 'email' => 'x@pt.test', 'phone' => '0812',
            'programs' => [['training_program_id' => $program->id, 'meetings' => $meeting]]])->assertSessionHasNoErrors();
        $this->assertSame('full', Customer::firstOrFail()->requested_programs[0]['payment_plan']);
    }

    public function test_review_is_prefilled_with_the_plan_and_approval_creates_the_payments(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['standard_price' => 8000000]);
        $customer = Customer::factory()->pendingApproval()->create([
            'requested_programs' => [['training_program_id' => $program->id, 'payment_plan' => 'installment', 'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']]]],
        ]);
        $finance = $this->finance();

        $this->actingAs($finance)->get(route('sales.review', $customer))->assertOk()
            ->assertSee('Payment plan')->assertSee('"payment_plan":"installment"', false)->assertSee('"fee":"8000000"', false)->assertSee('Rp 8.000.000');

        $meetings = [];
        foreach (['2026-10-06', '2026-10-08', '2026-10-13', '2026-10-15'] as $date) {
            $meetings[] = ['meeting_date' => $date, 'start_time' => '20:00', 'end_time' => '21:30'];
        }
        $this->actingAs($finance)->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'fee' => 8000000, 'payment_plan' => 'installment', 'meetings' => $meetings,
        ]]])->assertRedirect(route('sales.show', $customer));

        $session = TrainingSession::firstOrFail();
        $this->assertSame('installment', $session->payment_plan);
        $this->assertSame(['4000000.00', '4000000.00'], $session->payments->pluck('amount')->all());
        $this->assertSame([null, 2], $session->payments->pluck('due_meeting_number')->all());

        Mail::assertSent(CustomerRegistrationApproved::class, function (CustomerRegistrationApproved $mail) {
            $mail->assertSeeInHtml('Fee Rp 8.000.000');
            $mail->assertSeeInHtml('Down payment (50%): Rp 4.000.000 (Upon registration)');
            $mail->assertSeeInHtml('Final payment (50%): Rp 4.000.000 (At meeting 2)');

            return true;
        });

        $this->actingAs($finance)->get(route('sales.show', $customer))->assertSee('Rp 0 / Rp 8.000.000');
    }

    public function test_approval_rejects_an_unknown_plan(): void
    {
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->finance())->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'payment_plan' => 'gratis',
            'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']],
        ]]])->assertSessionHasErrors('programs.0.payment_plan');
    }

    public function test_customer_sees_amounts_and_status_in_the_portal(): void
    {
        $account = Account::factory()->create(['is_active' => true]);
        $customer = Customer::factory()->withPortalAccess('rahasia123')->create();
        $session = $this->sessionWithMeetings(8, 'installment');
        $session->update(['customer_id' => $customer->id]);
        $this->actingAs($this->finance(), 'web')->post(route('training.payments.pay', $session->payments()->first()), ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash']);
        $this->post(route('portal.logout'));

        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'rahasia123']);

        $this->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Payments')->assertSee('Rp 8.000.000')->assertSee('Down payment (50%)')->assertSee('Rp 4.000.000')
            ->assertSee('Paid')->assertSee('At meeting 4')->assertSee('Upcoming');
    }
}
