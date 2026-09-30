<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Mail\CustomerRegistrationReceived;
use App\Mail\CustomerRegistrationRejected;
use App\Mail\LeaveRequestReviewed;
use App\Mail\MeetingRescheduleApproved;
use App\Mail\MeetingRescheduleRejected;
use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\ProjectTaskNotification;
use App\Mail\StaffRegistrationSubmitted;
use App\Mail\StaffRescheduleRequested;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\MeetingRescheduleRequest;
use App\Models\ProjectTask;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Services\CustomerCodeGenerator;
use App\Services\SessionPaymentPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Mail::fake() never renders a template, and every send is wrapped in a try/catch that only logs,
 * so a broken template would go unnoticed. These tests build each email for real.
 */
class MailRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sessionWithPayments(Customer $customer): TrainingSession
    {
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'fee' => 10000000, 'payment_plan' => 'installment']);
        TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => '2026-10-06']);
        SessionPaymentPlan::generate($session->refresh());

        return $session->refresh();
    }

    public function test_customer_registration_emails_render(): void
    {
        $customer = Customer::factory()->pendingApproval()->create(['name' => 'Budi Santoso', 'rejection_reason' => 'Jadwal penuh', 'status_token' => 'status-token-123']);

        $received = (new CustomerRegistrationReceived($customer))->render();
        $this->assertStringContainsString('Budi Santoso', $received);
        $this->assertStringContainsString(route('customer-registration.status', 'status-token-123'), $received);
        $this->assertStringContainsString('Jadwal penuh', (new CustomerRegistrationRejected($customer))->render());

        $customer->forceFill(['registration_status' => Customer::REGISTRATION_COMPLETE, 'customer_code' => CustomerCodeGenerator::next(now())])->save();
        $this->sessionWithPayments($customer);

        $html = (new CustomerRegistrationApproved($customer->refresh()))->render();

        $this->assertStringContainsString($customer->customer_code, $html);
        $this->assertStringContainsString(route('portal.login'), $html);
    }

    public function test_payment_emails_render(): void
    {
        $customer = Customer::factory()->create(['name' => 'PT Maju Jaya', 'email' => 'hrd@maju.test']);
        $payment = $this->sessionWithPayments($customer)->payments->first();
        $payment->forceFill(['paid_date' => '2026-09-15'])->save();

        $invoice = (new PaymentInvoiceMail($payment))->render();
        $this->assertStringContainsString($payment->invoiceNumber(), $invoice);
        $this->assertStringContainsString('PT Maju Jaya', $invoice);

        $this->assertStringContainsString('Thank you for your payment', (new PaymentReceivedMail($payment))->render());
    }

    public function test_meeting_reschedule_emails_render(): void
    {
        $customer = Customer::factory()->create(['name' => 'Siti Aminah']);
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id]);
        $meeting = TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'start_time' => '09:00', 'end_time' => '12:00']);
        $request = MeetingRescheduleRequest::factory()->create(['training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id]);

        $approved = (new MeetingRescheduleApproved($request, 'Tue, 06 Oct 2026 · 09:00 – 12:00'))->render();
        $this->assertStringContainsString('Siti Aminah', $approved);
        $this->assertStringContainsString('Tue, 06 Oct 2026', $approved);

        $this->assertStringContainsString('Siti Aminah', (new MeetingRescheduleRejected($request))->render());
    }

    public function test_staff_and_leave_emails_render(): void
    {
        $customer = Customer::factory()->pendingApproval()->create(['name' => 'Budi Santoso', 'customer_type' => 'company']);
        $html = (new StaffRegistrationSubmitted($customer))->render();
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString(route('sales.review', $customer), $html);

        $session = TrainingSession::factory()->create(['customer_id' => $customer->id]);
        $meeting = TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'start_time' => '09:00', 'end_time' => '12:00']);
        $request = MeetingRescheduleRequest::factory()->create(['training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id, 'reason' => 'Ada acara keluarga']);
        $html = (new StaffRescheduleRequested($request))->render();
        $this->assertStringContainsString('Ada acara keluarga', $html);
        $this->assertStringContainsString(route('training.edit', $session), $html);

        $leave = LeaveRequest::factory()->create(['start_date' => '2026-03-02', 'end_date' => '2026-03-04', 'days' => 3, 'status' => 'approved']);
        $this->assertStringContainsString('was approved', (new LeaveRequestReviewed($leave))->render());
        $this->assertStringContainsString('unable to approve', (new LeaveRequestReviewed($leave->forceFill(['status' => 'rejected'])))->render());
    }

    public function test_project_task_emails_render_for_every_type(): void
    {
        $task = ProjectTask::factory()->create(['title' => 'Bangun dashboard']);
        $task->load('project');
        $employee = Employee::factory()->create(['name' => 'Rina', 'email' => 'rina@example.com']);

        foreach ([ProjectTaskNotification::ASSIGNED, ProjectTaskNotification::UPDATED, ProjectTaskNotification::UNASSIGNED] as $type) {
            $changes = $type === ProjectTaskNotification::UPDATED ? ['Status' => ['To do', 'In progress']] : [];
            $html = (new ProjectTaskNotification($task, $employee, $type, $changes))->render();

            $this->assertStringContainsString('Bangun dashboard', $html);
            $this->assertStringContainsString(route('projects.show', $task->project_id), $html);
        }
    }
}
