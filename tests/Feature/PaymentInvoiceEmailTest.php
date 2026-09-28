<?php

namespace Tests\Feature;

use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Models\Account;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use App\Services\PaymentInvoices;
use App\Services\SessionPaymentPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentInvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-15 10:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function corporateSession(int $minutes = 420): TrainingSession
    {
        $program = TrainingProgram::factory()->create(['session_minutes' => $minutes]);
        $session = TrainingSession::factory()->create([
            'training_program_id' => $program->id, 'customer_id' => Customer::factory()->create(['email' => 'hrd@pt.test'])->id,
            'fee' => 10000000, 'payment_plan' => 'installment', 'status' => 'planned',
        ]);
        TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => '2026-10-06']);
        SessionPaymentPlan::generate($session->refresh());

        return $session->refresh();
    }

    public function test_full_day_program_splits_dp_before_and_final_after_the_training(): void
    {
        $session = $this->corporateSession();
        [$dp, $final] = $session->payments->all();

        $this->assertSame('installment', $session->payment_plan);
        $this->assertSame('5000000.00', $dp->amount);
        $this->assertSame('Upon registration', $dp->dueLabel());
        $this->assertTrue($final->due_after_completion);
        $this->assertNull($final->due_meeting_number);
        $this->assertSame('After the training is completed', $final->dueLabel());
        $this->assertTrue($dp->isDue());
        $this->assertFalse($final->fresh('session.meetings')->isDue());

        $session->update(['status' => 'completed']);
        $this->assertTrue($final->fresh('session.meetings')->isDue());
    }

    public function test_dp_invoice_goes_out_at_once_and_the_final_one_only_after_completion(): void
    {
        $session = $this->corporateSession();

        $this->assertSame(1, PaymentInvoices::sendDue($session));
        Mail::assertSent(PaymentInvoiceMail::class, fn ($mail) => $mail->hasTo('hrd@pt.test') && $mail->payment->label === 'Down payment (50%)');

        $this->assertSame(0, PaymentInvoices::sendDue($session), 'no second invoice for the same payment');
        $this->assertSame(0, PaymentInvoices::sendAllDue());

        $session->update(['status' => 'completed']);
        $this->assertSame(1, PaymentInvoices::sendAllDue());
        Mail::assertSent(PaymentInvoiceMail::class, 2);
    }

    public function test_confirming_a_payment_emails_a_thank_you(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $finance = User::factory()->create();
        $finance->assignRole('Finance');
        $session = $this->corporateSession();
        $account = Account::factory()->create(['is_active' => true]);

        $this->actingAs($finance)->post(route('training.payments.pay', $session->payments->first()), ['account_id' => $account->id, 'paid_date' => '2026-09-16', 'payment_method' => 'Cash'])
            ->assertSessionHasNoErrors();

        Mail::assertSent(PaymentReceivedMail::class, fn ($mail) => $mail->hasTo('hrd@pt.test'));
    }

    public function test_short_program_uses_meeting_based_installments_and_no_email_without_an_address(): void
    {
        $session = $this->corporateSession(60); // single short meeting: forced to full
        $this->assertSame('full', $session->payment_plan);

        $session->customer->forceFill(['email' => null])->save();
        $this->assertSame(0, PaymentInvoices::sendDue($session->fresh()));
        Mail::assertNothingSent();
    }
}
