<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationReceived;
use App\Mail\LeaveRequestReviewed;
use App\Mail\StaffRegistrationSubmitted;
use App\Mail\StaffRescheduleRequested;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\MeetingRescheduleRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\TrainingSessionPayment;
use App\Models\User;
use App\Services\PaymentInvoices;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** The owner and staff get emailed about what waits for them; employees hear the outcome of their requests. */
class StaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 10:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $role, string $email, bool $active = true): User
    {
        $user = User::factory()->create(['email' => $email, 'is_active' => $active]);
        $user->assignRole($role);

        return $user;
    }

    public function test_a_submitted_registration_emails_everyone_who_can_approve_it(): void
    {
        $this->userWithRole('Super Admin', 'owner@example.com');
        $this->userWithRole('Finance', 'finance@example.com');
        $this->userWithRole('Staff', 'staff@example.com');
        $this->userWithRole('Super Admin', 'former-owner@example.com', active: false);

        $customer = Customer::factory()->awaitingCustomer()->create(['customer_type' => 'company']);
        $token = $customer->issueRegistrationToken();

        $this->post(route('customer-registration.store', $token), ['name' => 'PT Pelanggan Baru', 'email' => 'a@b.test', 'phone' => '0812'])->assertRedirect();

        Mail::assertSent(StaffRegistrationSubmitted::class, 2);
        Mail::assertSent(StaffRegistrationSubmitted::class, fn ($mail) => $mail->hasTo('owner@example.com') && ! $mail->hasTo('finance@example.com'));
        Mail::assertSent(StaffRegistrationSubmitted::class, fn ($mail) => $mail->hasTo('finance@example.com') && ! $mail->hasTo('owner@example.com'));
        Mail::assertNotSent(StaffRegistrationSubmitted::class, fn ($mail) => $mail->hasTo('staff@example.com') || $mail->hasTo('former-owner@example.com'));
        Mail::assertSent(CustomerRegistrationReceived::class, fn ($mail) => $mail->hasTo('a@b.test'));
    }

    public function test_a_failing_mail_server_does_not_block_the_registration_or_the_staff_notice(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
        $this->userWithRole('Super Admin', 'owner@example.com');

        $customer = Customer::factory()->awaitingCustomer()->create(['customer_type' => 'company']);

        $this->post(route('customer-registration.store', $customer->issueRegistrationToken()), ['name' => 'Tetap Masuk', 'email' => 'x@y.test', 'phone' => '0812'])
            ->assertRedirect();

        $this->assertTrue($customer->fresh()->isPendingApproval());
    }

    public function test_a_reschedule_request_emails_the_people_who_manage_training(): void
    {
        $this->userWithRole('Super Admin', 'owner@example.com');
        $this->userWithRole('Staff', 'staff@example.com');

        $customer = Customer::factory()->withPortalAccess('rahasia123')->create(['email' => 'cust@example.com']);
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'status' => 'planned']);
        $meeting = TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id, 'meeting_date' => '2026-09-19', 'start_time' => '09:00', 'end_time' => '10:30', 'is_completed' => false,
        ]);

        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'rahasia123'])->assertSessionDoesntHaveErrors();
        $this->post(route('portal.reschedule-requests.store'), [
            'training_session_meeting_id' => $meeting->id, 'requested_date' => '2026-09-26', 'requested_start_time' => '10:30', 'requested_end_time' => '12:00', 'reason' => 'Ada acara keluarga',
        ])->assertRedirect(route('portal.dashboard'));

        Mail::assertSent(StaffRescheduleRequested::class, 1);
        Mail::assertSent(StaffRescheduleRequested::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->rescheduleRequest->is(MeetingRescheduleRequest::firstOrFail()));
        Mail::assertNotSent(StaffRescheduleRequested::class, fn ($mail) => $mail->hasTo('staff@example.com'));
    }

    public function test_the_employee_is_emailed_when_their_leave_is_approved_or_rejected(): void
    {
        $hr = $this->userWithRole('Finance', 'hr@example.com');
        $employee = Employee::factory()->create(['email' => 'rina@example.com']);
        $approved = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-03-02', 'end_date' => '2026-03-04', 'days' => 3]);
        $rejected = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-04-06', 'end_date' => '2026-04-07', 'days' => 2]);

        $this->actingAs($hr)->post(route('hr.leaves.approve', $approved))
            ->assertSessionHas('status', 'Leave request approved. The employee was notified by email.');
        $this->actingAs($hr)->post(route('hr.leaves.reject', $rejected))
            ->assertSessionHas('status', 'Leave request rejected. The employee was notified by email.');

        Mail::assertSent(LeaveRequestReviewed::class, 2);
        Mail::assertSent(LeaveRequestReviewed::class, fn ($mail) => $mail->hasTo('rina@example.com') && $mail->leave->is($approved) && $mail->leave->status === 'approved');
        Mail::assertSent(LeaveRequestReviewed::class, fn ($mail) => $mail->hasTo('rina@example.com') && $mail->leave->is($rejected) && $mail->leave->status === 'rejected');
    }

    public function test_hr_is_told_when_the_employee_could_not_be_emailed(): void
    {
        $hr = $this->userWithRole('Finance', 'hr@example.com');
        $noEmail = LeaveRequest::factory()->create(['employee_id' => Employee::factory()->create(['email' => null])->id, 'start_date' => '2026-03-02', 'end_date' => '2026-03-04', 'days' => 3]);

        $this->actingAs($hr)->post(route('hr.leaves.approve', $noEmail))->assertSessionHas('error', fn (string $error) => str_contains($error, 'could NOT be notified'));

        Mail::assertNotSent(LeaveRequestReviewed::class);
        $this->assertSame('approved', $noEmail->fresh()->status);
    }

    public function test_the_thank_you_email_reports_whether_it_went_out(): void
    {
        $withEmail = TrainingSessionPayment::factory()->create([
            'training_session_id' => TrainingSession::factory()->create(['customer_id' => Customer::factory()->create(['email' => 'cust@example.com'])->id])->id,
        ]);
        $withoutEmail = TrainingSessionPayment::factory()->create([
            'training_session_id' => TrainingSession::factory()->create(['customer_id' => Customer::factory()->create(['email' => null])->id])->id,
        ]);

        $this->assertTrue(PaymentInvoices::sendThanks($withEmail->load('session.customer')));
        $this->assertFalse(PaymentInvoices::sendThanks($withoutEmail->load('session.customer')));

        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
        $this->assertFalse(PaymentInvoices::sendThanks($withEmail));
    }
}
