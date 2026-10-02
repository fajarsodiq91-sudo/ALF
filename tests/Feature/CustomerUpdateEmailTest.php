<?php

namespace Tests\Feature;

use App\Mail\TrainingUpdateMail;
use App\Models\Customer;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerUpdateEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TrainingSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
        $this->session = TrainingSession::factory()->create([
            'customer_id' => Customer::factory()->create(['email' => 'client@pt.test'])->id,
            'status' => 'planned',
        ]);
    }

    public function test_marking_a_meeting_done_emails_only_the_customer(): void
    {
        $meeting = TrainingSessionMeeting::factory()->create(['training_session_id' => $this->session->id, 'meeting_date' => '2026-10-06']);

        $this->actingAs($this->admin)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();

        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => $mail->hasTo('client@pt.test') && str_contains($mail->heading, 'done'));
        Mail::assertSent(TrainingUpdateMail::class, 1);
    }

    public function test_completing_a_session_emails_the_customer(): void
    {
        $this->actingAs($this->admin)->post(route('training.complete', $this->session))->assertRedirect();

        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => $mail->hasTo('client@pt.test') && str_contains($mail->heading, 'Completed'));
    }

    public function test_the_mail_renders_with_the_details(): void
    {
        $mail = new TrainingUpdateMail($this->session->customer, 'Your meeting is marked as done', 'Done.', ['Program' => 'Laravel 101']);

        $mail->assertSeeInHtml('Laravel 101');
        $mail->assertSeeInHtml($this->session->customer->name);
    }
}
