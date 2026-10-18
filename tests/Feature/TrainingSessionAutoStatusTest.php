<?php

namespace Tests\Feature;

use App\Mail\TrainingUpdateMail;
use App\Models\Certificate;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingSessionAutoStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Carbon::setTestNow('2026-10-17 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{0: TrainingSession, 1: array<int, TrainingSessionMeeting>} */
    private function sessionWithMeetings(int $count, string $type = 'learning'): array
    {
        $customer = Customer::factory()->create(['email' => 'client@pt.test']);
        $customer->forceFill(['customer_code' => '2610'.str_pad((string) $customer->id, 2, '0', STR_PAD_LEFT), 'must_change_password' => false])->save();
        $session = TrainingSession::factory()->create([
            'customer_id' => $customer->id,
            'training_program_id' => TrainingProgram::factory()->create(['program_type' => $type])->id,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-10', 'status' => 'planned',
        ]);
        $meetings = collect($count > 0 ? range(1, $count) : [])->map(fn ($i) => TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => '2026-10-0'.$i]))->all();

        return [$session, $meetings];
    }

    private function toggle(TrainingSessionMeeting $meeting): void
    {
        $this->actingAs($this->admin)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();
    }

    private function save(TrainingSession $session, string $status)
    {
        return $this->actingAs($this->admin)->put(route('training.update', $session), [
            'training_program_id' => $session->training_program_id, 'customer_id' => $session->customer_id,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-10', 'delivery_mode' => $session->delivery_mode,
            'participants_count' => 1, 'fee' => $session->fee, 'payment_plan' => 'full', 'status' => $status,
        ]);
    }

    public function test_the_status_follows_the_meetings_and_completing_issues_the_certificate(): void
    {
        [$session, [$first, $second]] = $this->sessionWithMeetings(2);

        $this->toggle($first);
        $this->assertSame('ongoing', $session->fresh()->status);
        $this->assertSame(0, Certificate::count());

        $this->toggle($second);
        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame(1, Certificate::released()->count());
        Mail::assertSent(TrainingUpdateMail::class, fn ($mail) => str_contains($mail->heading, 'Your certificate is ready'));

        $this->toggle($second);
        $this->assertSame('ongoing', $session->fresh()->status);
        $this->assertSame(0, Certificate::released()->count());

        $this->toggle($first);
        $this->assertSame('planned', $session->fresh()->status);
    }

    public function test_a_cancelled_session_stays_cancelled(): void
    {
        [$session, [$meeting]] = $this->sessionWithMeetings(1);
        $session->update(['status' => 'cancelled']);

        $this->toggle($meeting);

        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertSame(0, Certificate::count());
    }

    public function test_adding_or_deleting_a_meeting_recomputes_the_status(): void
    {
        [$session, [$only]] = $this->sessionWithMeetings(1);
        $this->toggle($only);
        $this->assertSame('completed', $session->fresh()->status);

        $this->actingAs($this->admin)->post(route('training.meetings.store', $session), ['meeting_date' => '2026-10-20', 'start_time' => '20:00', 'end_time' => '21:30'])->assertSessionHasNoErrors();
        $this->assertSame('ongoing', $session->fresh()->status);

        $this->actingAs($this->admin)->delete(route('training.meetings.destroy', TrainingSessionMeeting::where('training_session_id', $session->id)->orderByDesc('id')->first()))->assertRedirect();
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_the_session_form_cannot_force_a_status_but_can_cancel_and_reopen(): void
    {
        [$session, [$first]] = $this->sessionWithMeetings(2);

        $this->save($session, 'completed')->assertRedirect();
        $this->assertSame('planned', $session->fresh()->status);
        $this->assertSame(0, Certificate::count());

        $this->toggle($first);
        $this->save($session, 'ongoing')->assertRedirect(); // a plain save keeps the current status
        $this->assertSame('ongoing', $session->fresh()->status);

        $this->save($session, 'cancelled')->assertRedirect();
        $this->assertSame('cancelled', $session->fresh()->status);

        $this->save($session, 'auto')->assertRedirect();
        $this->assertSame('ongoing', $session->fresh()->status);
    }

    public function test_a_session_without_meetings_keeps_its_manual_status(): void
    {
        [$session] = $this->sessionWithMeetings(0);

        $this->save($session, 'completed')->assertRedirect();

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame(1, Certificate::count());
    }

    public function test_mark_as_done_is_only_for_sessions_without_meetings(): void
    {
        [$withMeetings] = $this->sessionWithMeetings(1);
        $this->actingAs($this->admin)->post(route('training.complete', $withMeetings))->assertSessionHas('error');
        $this->assertSame('planned', $withMeetings->fresh()->status);

        [$without] = $this->sessionWithMeetings(0);
        $this->actingAs($this->admin)->post(route('training.complete', $without))->assertSessionHas('status');
        $this->assertSame('completed', $without->fresh()->status);
    }

    public function test_the_ended_marker_depends_on_whether_the_session_has_meetings(): void
    {
        [$withMeetings] = $this->sessionWithMeetings(2);
        [$without] = $this->sessionWithMeetings(0);

        $response = $this->actingAs($this->admin)->get(route('training.index'))->assertOk();

        $response->assertSee('Ended — meetings not all marked done')->assertSee('Ended — mark as done');
        $response->assertSee(route('training.complete', $without), false)->assertDontSee(route('training.complete', $withMeetings), false);
    }

    public function test_the_edit_page_warns_before_the_last_meeting_is_marked_done(): void
    {
        [$session, [$first]] = $this->sessionWithMeetings(2);

        $this->actingAs($this->admin)->get(route('training.edit', $session))->assertOk()->assertDontSee('This is the last open meeting');

        $this->toggle($first);

        $this->actingAs($this->admin)->get(route('training.edit', $session))->assertOk()->assertSee('This is the last open meeting');
    }
}
