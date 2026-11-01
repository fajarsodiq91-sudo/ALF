<?php

namespace Tests\Feature;

use App\Mail\StaffMeetingSummary;
use App\Mail\TrainingUpdateMail;
use App\Models\Customer;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Customers are reminded the day before and an hour before a meeting; the staff get one daily summary. */
class MeetingReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 07:30:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function meeting(string $date, ?string $start = '09:00', array $session = []): TrainingSessionMeeting
    {
        $customer = Customer::factory()->create(['email' => 'hrd@pt.test']);
        $session = TrainingSession::factory()->create($session + ['customer_id' => $customer->id, 'status' => 'planned']);

        return TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id, 'meeting_date' => $date, 'start_time' => $start, 'end_time' => $start ? '12:00' : null,
        ]);
    }

    public function test_the_day_before_reminder_goes_to_the_customer_once(): void
    {
        $meeting = $this->meeting('2026-09-16');
        $this->meeting('2026-09-17');

        $this->artisan('meetings:send-reminders day-before')->assertExitCode(0);
        $this->artisan('meetings:send-reminders day-before')->assertExitCode(0);

        Mail::assertSent(TrainingUpdateMail::class, 1);
        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => $mail->hasTo('hrd@pt.test') && str_contains($mail->heading, 'tomorrow'));
        $this->assertNotNull($meeting->refresh()->day_before_reminded_at);
    }

    public function test_participants_are_reminded_too(): void
    {
        $meeting = $this->meeting('2026-09-16');
        $participant = Customer::factory()->create(['email' => 'peserta@example.com']);
        $meeting->session->participants()->attach($participant);

        $this->artisan('meetings:send-reminders day-before');

        Mail::assertSent(TrainingUpdateMail::class, 2);
        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => $mail->hasTo('peserta@example.com'));
    }

    public function test_the_hour_before_reminder_waits_until_an_hour_before_the_start(): void
    {
        $meeting = $this->meeting('2026-09-15', '09:00');

        Carbon::setTestNow('2026-09-15 07:59:00');
        $this->artisan('meetings:send-reminders hour-before');
        Mail::assertNothingSent();

        Carbon::setTestNow('2026-09-15 08:00:00');
        $this->artisan('meetings:send-reminders hour-before');
        $this->artisan('meetings:send-reminders hour-before');

        Mail::assertSent(TrainingUpdateMail::class, 1);
        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => str_contains($mail->heading, '1 hour'));
        $this->assertNotNull($meeting->refresh()->hour_before_reminded_at);
    }

    public function test_the_hour_before_reminder_works_across_midnight(): void
    {
        $this->meeting('2026-09-16', '00:30');

        Carbon::setTestNow('2026-09-15 23:45:00');
        $this->artisan('meetings:send-reminders hour-before');

        Mail::assertSent(TrainingUpdateMail::class, 1);
    }

    public function test_no_reminder_for_started_completed_or_cancelled_meetings_or_ones_without_a_time(): void
    {
        Carbon::setTestNow('2026-09-15 08:30:00');
        $this->meeting('2026-09-15', '08:00');
        $this->meeting('2026-09-15', null);
        $this->meeting('2026-09-15', '09:00')->update(['is_completed' => true]);
        $this->meeting('2026-09-15', '09:00', ['status' => 'cancelled'])->session->update(['status' => 'cancelled']);

        $this->artisan('meetings:send-reminders hour-before');
        $this->artisan('meetings:send-reminders day-before');

        Mail::assertNothingSent();
    }

    public function test_moving_a_meeting_makes_it_due_its_reminders_again(): void
    {
        $meeting = $this->meeting('2026-09-16');
        $this->artisan('meetings:send-reminders day-before');

        $meeting->update(['meeting_date' => '2026-09-17']);
        $this->assertNull($meeting->refresh()->day_before_reminded_at);

        Carbon::setTestNow('2026-09-16 07:30:00');
        $this->artisan('meetings:send-reminders day-before');

        Mail::assertSent(TrainingUpdateMail::class, 2);
    }

    public function test_staff_get_one_summary_of_today_and_tomorrow(): void
    {
        foreach ([['owner@example.com', 'Super Admin'], ['finance@example.com', 'Finance']] as [$email, $role]) {
            User::factory()->create(['email' => $email, 'is_active' => true])->assignRole($role);
        }
        $this->meeting('2026-09-15');
        $this->meeting('2026-09-15', '13:00');
        $this->meeting('2026-09-16');
        $this->meeting('2026-09-20');
        User::factory()->create(['email' => 'staff@example.com', 'is_active' => true])->assignRole('Staff');

        $this->artisan('meetings:send-reminders staff-summary');

        Mail::assertSent(StaffMeetingSummary::class, 2);
        Mail::assertNotSent(StaffMeetingSummary::class, fn ($mail) => $mail->hasTo('staff@example.com'));
        Mail::assertSent(StaffMeetingSummary::class, fn ($mail) => $mail->today->count() === 2 && $mail->tomorrow->count() === 1);
    }

    public function test_no_summary_when_there_are_no_meetings(): void
    {
        User::factory()->create(['is_active' => true])->assignRole('Super Admin');
        $this->meeting('2026-09-25');

        $this->artisan('meetings:send-reminders staff-summary');

        Mail::assertNothingSent();
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $this->artisan('meetings:send-reminders nonsense')->assertExitCode(2);
    }

    public function test_the_summary_email_renders(): void
    {
        $meeting = $this->meeting('2026-09-15')->load('session.program', 'session.customer');

        $html = (new StaffMeetingSummary(collect([$meeting]), collect()))->render();

        $this->assertStringContainsString($meeting->session->program->name, $html);
    }
}
