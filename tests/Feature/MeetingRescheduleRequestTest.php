<?php

namespace Tests\Feature;

use App\Mail\MeetingRescheduleApproved;
use App\Mail\MeetingRescheduleRejected;
use App\Models\Customer;
use App\Models\MeetingRescheduleRequest;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MeetingRescheduleRequestTest extends TestCase
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

    /** @var array<int, string> plain passwords of the customers created here, by id */
    private array $passwords = [];

    private function customer(?string $password = null, array $attributes = []): Customer
    {
        $customer = Customer::factory()->withPortalAccess($password)->create(['email' => fake()->unique()->safeEmail(), ...$attributes]);
        $this->passwords[$customer->id] = $password ?? $customer->customer_code;

        return $customer;
    }

    /** Logs in through the real portal form (actingAs would swap the app's default guard). */
    private function loginAs(Customer $customer): void
    {
        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => $this->passwords[$customer->id]])
            ->assertSessionDoesntHaveErrors();
    }

    private function trainingManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance'); // has training.view + training.manage

        return $user;
    }

    private function upcomingMeeting(Customer $customer, array $sessionAttributes = [], array $meetingAttributes = []): TrainingSessionMeeting
    {
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'status' => 'planned', ...$sessionAttributes]);

        return TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id,
            'meeting_date' => '2026-09-19', // a Saturday, inside the default operating hours
            'start_time' => '09:00',
            'end_time' => '10:30',
            'is_completed' => false,
            ...$meetingAttributes,
        ]);
    }

    private function requestPayload(TrainingSessionMeeting $meeting, array $overrides = []): array
    {
        return [
            'training_session_meeting_id' => $meeting->id,
            'requested_date' => '2026-09-26',
            'requested_start_time' => '10:40',
            'requested_end_time' => '12:10',
            'reason' => 'Ada acara keluarga',
            ...$overrides,
        ];
    }

    public function test_customer_can_request_a_reschedule_for_their_own_upcoming_meeting(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertRedirect(route('portal.dashboard'));

        $request = MeetingRescheduleRequest::firstOrFail();
        $this->assertSame($meeting->id, $request->training_session_meeting_id);
        $this->assertSame($customer->id, $request->customer_id);
        $this->assertSame('2026-09-26', $request->requested_date->toDateString());
        $this->assertSame('pending', $request->status);
        $this->assertSame('Ada acara keluarga', $request->reason);

        $this->get(route('portal.dashboard'))->assertSee('Pending review');
    }

    public function test_customer_cannot_request_reschedule_for_someone_elses_meeting(): void
    {
        $customer = $this->customer('rahasia123');
        $stranger = $this->upcomingMeeting(Customer::factory()->create());
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($stranger))
            ->assertSessionHasErrors('training_session_meeting_id');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_participant_cannot_request_reschedule_for_a_session_they_only_joined(): void
    {
        $owner = $this->customer('pemilik123');
        $participant = $this->customer('peserta123');
        $meeting = $this->upcomingMeeting($owner);
        $meeting->session->participants()->attach($participant->id);
        $this->loginAs($participant);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertSessionHasErrors('training_session_meeting_id');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_request_reschedule_for_a_completed_meeting(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer, meetingAttributes: ['is_completed' => true]);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertSessionHasErrors('training_session_meeting_id');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_request_reschedule_for_a_cancelled_session(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer, sessionAttributes: ['status' => 'cancelled']);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertSessionHasErrors('training_session_meeting_id');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_submit_a_second_pending_request_for_the_same_meeting(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))->assertRedirect();
        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting, ['requested_date' => '2026-10-03']))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_request_the_same_slot_the_meeting_already_has(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting, [
            'requested_date' => '2026-09-19', 'requested_start_time' => '09:00', 'requested_end_time' => '10:30',
        ]))->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_request_a_slot_outside_operating_hours(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $this->loginAs($customer);

        // 2026-09-16 is a Wednesday: not an operating day by default.
        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting, ['requested_date' => '2026-09-16']))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_request_a_slot_already_booked_by_another_meeting(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $this->upcomingMeeting(Customer::factory()->create(), meetingAttributes: [
            'meeting_date' => '2026-09-26', 'start_time' => '10:40', 'end_time' => '12:10',
        ]);
        $this->loginAs($customer);

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_can_cancel_their_own_pending_request(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id, 'status' => 'pending',
        ]);
        $this->loginAs($customer);

        $this->delete(route('portal.reschedule-requests.destroy', $request))->assertRedirect(route('portal.dashboard'));

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_cancel_someone_elses_request(): void
    {
        $owner = $this->customer('pemilik123');
        $stranger = $this->customer('lain123');
        $meeting = $this->upcomingMeeting($owner);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'customer_id' => $owner->id, 'status' => 'pending',
        ]);
        $this->loginAs($stranger);

        $this->delete(route('portal.reschedule-requests.destroy', $request))->assertForbidden();

        $this->assertSame(1, MeetingRescheduleRequest::count());
    }

    public function test_customer_cannot_cancel_an_already_reviewed_request(): void
    {
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id, 'status' => 'approved',
        ]);
        $this->loginAs($customer);

        $this->delete(route('portal.reschedule-requests.destroy', $request))->assertRedirect()->assertSessionHas('error');

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_unauthenticated_visitor_cannot_submit_a_reschedule_request(): void
    {
        $meeting = $this->upcomingMeeting(Customer::factory()->create());

        $this->post(route('portal.reschedule-requests.store'), $this->requestPayload($meeting))
            ->assertRedirect(route('portal.login'));

        $this->assertSame(0, MeetingRescheduleRequest::count());
    }

    public function test_staff_can_approve_a_reschedule_request_and_the_customer_is_notified(): void
    {
        Mail::fake();
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id,
            'customer_id' => $customer->id,
            'requested_date' => '2026-09-26',
            'requested_start_time' => '10:40',
            'requested_end_time' => '12:10',
            'status' => 'pending',
        ]);

        $this->actingAs($this->trainingManager())
            ->post(route('training.reschedule-requests.approve', $request))
            ->assertRedirect(route('training.edit', $meeting->training_session_id));

        $meeting->refresh();
        $this->assertSame('2026-09-26', $meeting->meeting_date->toDateString());
        $this->assertSame('10:40', $meeting->start_time);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->reviewed_at);

        Mail::assertSent(MeetingRescheduleApproved::class, fn ($mail) => $mail->hasTo($customer->email));
    }

    public function test_staff_cannot_approve_a_request_that_conflicts_with_a_meeting_booked_since(): void
    {
        Mail::fake();
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id,
            'customer_id' => $customer->id,
            'requested_date' => '2026-09-26',
            'requested_start_time' => '10:40',
            'requested_end_time' => '12:10',
            'status' => 'pending',
        ]);
        // Someone else grabbed that slot after the request was submitted.
        $this->upcomingMeeting(Customer::factory()->create(), meetingAttributes: [
            'meeting_date' => '2026-09-26', 'start_time' => '10:40', 'end_time' => '12:10',
        ]);

        $this->actingAs($this->trainingManager())
            ->post(route('training.reschedule-requests.approve', $request))
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame('2026-09-19', $meeting->fresh()->meeting_date->toDateString());
        $this->assertSame('pending', $request->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_staff_can_reject_a_reschedule_request_and_the_meeting_is_unchanged(): void
    {
        Mail::fake();
        $customer = $this->customer('rahasia123');
        $meeting = $this->upcomingMeeting($customer);
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id, 'status' => 'pending',
        ]);

        $this->actingAs($this->trainingManager())
            ->post(route('training.reschedule-requests.reject', $request))
            ->assertRedirect(route('training.edit', $meeting->training_session_id));

        $this->assertSame('2026-09-19', $meeting->fresh()->meeting_date->toDateString());
        $this->assertSame('rejected', $request->fresh()->status);
        Mail::assertSent(MeetingRescheduleRejected::class, fn ($mail) => $mail->hasTo($customer->email));
    }

    public function test_reviewing_an_already_reviewed_request_is_refused(): void
    {
        $meeting = $this->upcomingMeeting($this->customer('rahasia123'));
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'status' => 'approved',
        ]);

        $this->actingAs($this->trainingManager())
            ->post(route('training.reschedule-requests.approve', $request))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_a_user_without_training_manage_cannot_approve_or_reject(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'training.view']);
        $meeting = $this->upcomingMeeting($this->customer('rahasia123'));
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id, 'status' => 'pending',
        ]);

        $this->actingAs($viewer)->post(route('training.reschedule-requests.approve', $request))->assertForbidden();
        $this->actingAs($viewer)->post(route('training.reschedule-requests.reject', $request))->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_dashboard_shows_a_follow_up_for_pending_reschedule_requests(): void
    {
        $meeting = $this->upcomingMeeting($this->customer('rahasia123'));
        MeetingRescheduleRequest::factory()->create(['training_session_meeting_id' => $meeting->id, 'status' => 'pending']);

        $this->actingAs($this->trainingManager())->get(route('dashboard'))
            ->assertSee('Reschedule requests')
            ->assertSee(route('training.index', ['reschedule_pending' => 1]));
    }

    public function test_training_edit_page_shows_the_pending_request_with_approve_and_reject_actions(): void
    {
        $meeting = $this->upcomingMeeting($this->customer('rahasia123'));
        $request = MeetingRescheduleRequest::factory()->create([
            'training_session_meeting_id' => $meeting->id,
            'requested_date' => '2026-09-26',
            'requested_start_time' => '10:40',
            'requested_end_time' => '12:10',
            'reason' => 'Ada acara keluarga',
            'status' => 'pending',
        ]);

        $this->actingAs($this->trainingManager())->get(route('training.edit', $meeting->training_session_id))
            ->assertSee('Customer requested a reschedule')
            ->assertSee('Ada acara keluarga')
            ->assertSee(route('training.reschedule-requests.approve', $request), false)
            ->assertSee(route('training.reschedule-requests.reject', $request), false);
    }
}
