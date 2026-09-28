<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use App\Services\BookedSlots;
use App\Services\OperatingHours;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CalendarBookingTest extends TestCase
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

    private function book(string $date, string $start, string $end, string $status = 'planned'): TrainingSessionMeeting
    {
        $session = TrainingSession::factory()->create(['status' => $status]);

        return TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => $date, 'start_time' => $start, 'end_time' => $end]);
    }

    /** @return array<string, string> */
    private function slot(string $date, string $start, string $end): array
    {
        return ['meeting_date' => $date, 'start_time' => $start, 'end_time' => $end];
    }

    public function test_scheduled_meetings_are_booked_but_cancelled_sessions_are_not(): void
    {
        $this->book('2026-10-06', '20:00', '21:30');
        $this->book('2026-10-08', '20:00', '21:30', 'cancelled');

        $keys = BookedSlots::keys();

        $this->assertSame(['2026-10-06|20:00-21:30'], $keys);
        $this->assertTrue(BookedSlots::conflicts('2026-10-06', '20:00', '21:30'));
        $this->assertFalse(BookedSlots::conflicts('2026-10-08', '20:00', '21:30'));
    }

    public function test_only_overlapping_times_on_the_same_day_conflict(): void
    {
        $this->book('2026-10-10', '09:00', '10:30');

        $this->assertTrue(BookedSlots::conflicts('2026-10-10', '09:00', '10:30'));
        $this->assertTrue(BookedSlots::conflicts('2026-10-10', '10:00', '11:00')); // overlaps the end
        $this->assertTrue(BookedSlots::conflicts('2026-10-10', '08:00', '09:30')); // overlaps the start
        $this->assertFalse(BookedSlots::conflicts('2026-10-10', '10:40', '12:10')); // the next slot: back to back is fine
        $this->assertFalse(BookedSlots::conflicts('2026-10-10', '10:30', '10:40'));
        $this->assertFalse(BookedSlots::conflicts('2026-10-11', '09:00', '10:30')); // another day
        $this->assertFalse(BookedSlots::conflicts('2026-10-10', null, null)); // no time, no clash
    }

    public function test_slots_outside_the_calendar_window_are_not_sent_to_the_browser(): void
    {
        $this->book('2026-09-01', '20:00', '21:30'); // in the past
        $this->book('2027-08-03', '20:00', '21:30'); // beyond the window
        $this->book('2026-10-06', '20:00', '21:30');

        $this->assertSame(['2026-10-06|20:00-21:30'], BookedSlots::keys());
    }

    public function test_pending_requests_hold_their_slots_for_everyone_except_their_owner(): void
    {
        $mine = Customer::factory()->pendingApproval()->create(['requested_programs' => [['training_program_id' => 1, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]]);
        Customer::factory()->pendingApproval()->create(['requested_programs' => [['training_program_id' => 1, 'meetings' => [$this->slot('2026-10-08', '20:00', '21:30')]]]]);
        Customer::factory()->create(['requested_programs' => [['training_program_id' => 1, 'meetings' => [$this->slot('2026-10-13', '20:00', '21:30')]]]]); // approved: its meetings are real ones

        $this->assertEqualsCanonicalizing(['2026-10-06|20:00-21:30', '2026-10-08|20:00-21:30'], BookedSlots::keys());
        $this->assertSame(['2026-10-08|20:00-21:30'], BookedSlots::keys($mine->id));
        $this->assertFalse(BookedSlots::conflicts('2026-10-06', '20:00', '21:30', $mine->id));
        $this->assertTrue(BookedSlots::conflicts('2026-10-06', '20:00', '21:30'));
    }

    public function test_rejected_requests_free_their_slots(): void
    {
        $customer = Customer::factory()->pendingApproval()->create(['requested_programs' => [['training_program_id' => 1, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]]);
        $this->assertTrue(BookedSlots::conflicts('2026-10-06', '20:00', '21:30'));

        $this->actingAs($this->finance(), 'web')->post(route('sales.reject', $customer));

        $this->assertFalse(BookedSlots::conflicts('2026-10-06', '20:00', '21:30'));
    }

    public function test_the_public_form_ships_the_taken_slots_and_the_calendar_picker(): void
    {
        $this->book('2026-10-06', '20:00', '21:30');
        TrainingProgram::factory()->create();
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $token = Customer::latest('id')->firstOrFail()->registration_token;

        $this->get(route('customer-registration.show', $token))->assertOk()
            ->assertSee('window.bookedSlots = ["2026-10-06|20:00-21:30"]', false)
            ->assertSee('function slotPicker()', false)
            ->assertSee('Choose on calendar')->assertSee('Booked')->assertSee('Available')
            ->assertSee('Our operating hours')
            ->assertDontSee('Time slot');
    }

    public function test_customers_cannot_book_a_slot_someone_else_holds(): void
    {
        Mail::fake();
        $this->book('2026-10-06', '20:00', '21:30');
        $program = TrainingProgram::factory()->create(['duration_days' => 1]);
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $invited = Customer::latest('id')->firstOrFail();
        $token = $invited->registration_token;
        $base = ['name' => 'PT Rebutan', 'email' => 'r@pt.test', 'phone' => '0812'];

        $this->post(route('customer-registration.store', $token), $base + ['programs' => [['training_program_id' => $program->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]])
            ->assertSessionHasErrors(['programs.0.meetings.0.meeting_date' => 'Tue, 06 Oct 2026, 20:00 – 21:30 has just been booked by someone else. Please choose another slot.']);
        $this->assertTrue($invited->fresh()->isAwaitingCustomer());

        $this->post(route('customer-registration.store', $token), $base + ['programs' => [['training_program_id' => $program->id, 'meetings' => [$this->slot('2026-10-08', '20:00', '21:30')]]]])
            ->assertSessionHasNoErrors();
    }

    public function test_two_customers_racing_for_one_slot_only_the_first_wins(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['duration_days' => 1]);
        $finance = $this->finance();
        $choice = ['programs' => [['training_program_id' => $program->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]];
        $tokens = [];
        foreach ([1, 2] as $i) {
            $this->actingAs($finance, 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
            $tokens[] = Customer::latest('id')->firstOrFail()->registration_token;
        }

        $this->post(route('customer-registration.store', $tokens[0]), ['name' => 'PT Pertama', 'email' => 'a@pt.test', 'phone' => '0812'] + $choice)->assertSessionHasNoErrors();
        $this->post(route('customer-registration.store', $tokens[1]), ['name' => 'PT Kedua', 'email' => 'b@pt.test', 'phone' => '0812'] + $choice)->assertSessionHasErrors('programs.0.meetings.0.meeting_date');
    }

    public function test_the_same_slot_twice_in_one_request_is_rejected(): void
    {
        $a = TrainingProgram::factory()->create();
        $b = TrainingProgram::factory()->create();
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $token = Customer::firstOrFail()->registration_token;

        $this->post(route('customer-registration.store', $token), ['name' => 'PT X', 'email' => 'x@pt.test', 'phone' => '0812', 'programs' => [
            ['training_program_id' => $a->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]],
            ['training_program_id' => $b->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]],
        ]])->assertSessionHasErrors('programs.1.meetings.0.meeting_date');
    }

    public function test_the_review_page_paints_other_customers_slots_but_not_the_customers_own_request(): void
    {
        $program = TrainingProgram::factory()->create();
        $this->book('2026-10-08', '20:00', '21:30');
        $customer = Customer::factory()->pendingApproval()->create(['requested_programs' => [['training_program_id' => $program->id, 'payment_plan' => 'full', 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]]);

        $this->actingAs($this->finance(), 'web')->get(route('sales.review', $customer))->assertOk()
            ->assertSee('window.bookedSlots = ["2026-10-08|20:00-21:30"]', false)
            ->assertDontSee('2026-10-06|20:00-21:30', false)
            ->assertSee('function slotPicker()', false)->assertSee('Choose on calendar')->assertSee('Operating hours');
    }

    public function test_approval_refuses_slots_booked_by_others_but_keeps_the_customers_own_request(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['duration_days' => 1]);
        $this->book('2026-10-08', '20:00', '21:30');
        $customer = Customer::factory()->pendingApproval()->create(['requested_programs' => [['training_program_id' => $program->id, 'payment_plan' => 'full', 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]]);
        $finance = $this->finance();
        $payload = fn (string $date) => ['programs' => [['training_program_id' => $program->id, 'delivery_mode' => 'online', 'fee' => 1000000, 'payment_plan' => 'full', 'meetings' => [$this->slot($date, '20:00', '21:30')]]]];

        $this->actingAs($finance)->post(route('sales.approve', $customer), $payload('2026-10-08'))
            ->assertSessionHasErrors('programs.0.meetings.0.meeting_date');
        $this->assertTrue($customer->fresh()->isPendingApproval());

        $this->actingAs($finance)->post(route('sales.approve', $customer), $payload('2026-10-06'))->assertRedirect(route('sales.show', $customer));
        Mail::assertSent(CustomerRegistrationApproved::class);
    }

    public function test_approval_refuses_the_same_slot_twice(): void
    {
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->finance(), 'web')->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online',
            'meetings' => [$this->slot('2026-10-06', '20:00', '21:30'), $this->slot('2026-10-06', '20:00', '21:30')],
        ]]])->assertSessionHasErrors('programs.0.meetings.1.meeting_date');
    }

    public function test_adding_a_meeting_to_a_session_refuses_a_booked_slot(): void
    {
        $this->book('2026-10-06', '20:00', '21:30');
        $session = TrainingSession::factory()->create();
        $finance = $this->finance();

        $this->actingAs($finance)->post(route('training.meetings.store', $session), $this->slot('2026-10-06', '20:00', '21:30'))
            ->assertSessionHasErrors('meeting_date');
        $this->assertSame(0, $session->meetings()->count());

        $this->actingAs($finance)->post(route('training.meetings.store', $session), $this->slot('2026-10-08', '20:00', '21:30'))->assertSessionHasNoErrors();
        $this->assertSame(1, $session->meetings()->count());
    }

    public function test_the_session_page_shows_the_calendar_picker_and_the_booked_slots(): void
    {
        $this->book('2026-10-06', '20:00', '21:30');
        $session = TrainingSession::factory()->create();

        $this->actingAs($this->finance(), 'web')->get(route('training.edit', $session))->assertOk()
            ->assertSee('window.bookedSlots = ["2026-10-06|20:00-21:30"]', false)->assertSee('Choose on calendar')->assertSee('function slotPicker()', false);
    }

    public function test_conflicts_are_still_checked_when_the_strict_hours_are_off(): void
    {
        OperatingHours::save(OperatingHours::defaults(), false);
        $this->book('2026-10-05', '14:00', '15:00'); // a Monday: allowed now
        $session = TrainingSession::factory()->create();

        $this->actingAs($this->finance(), 'web')->post(route('training.meetings.store', $session), $this->slot('2026-10-05', '14:30', '15:30'))->assertSessionHasErrors('meeting_date');
        $this->actingAs($this->finance(), 'web')->post(route('training.meetings.store', $session), $this->slot('2026-10-05', '15:00', '16:00'))->assertSessionHasNoErrors();
    }

    public function test_master_data_has_an_operating_hours_page_with_a_weekly_calendar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get(route('masterdata.index'))->assertOk()->assertSee(route('masterdata.hours.edit'), false)->assertSee('Operating Hours');
        $this->actingAs($admin)->get(route('masterdata.hours.edit'))->assertOk()
            ->assertSee('Weekly calendar')->assertSee('window.weekRange', false)
            ->assertSee('"start":"09:00","end":"10:30"', false)->assertSee('Add slot')->assertSee('Save Operating Hours');
    }

    public function test_the_weekly_calendar_data_lists_all_seven_days(): void
    {
        $days = OperatingHours::forWeekCalendar();

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], array_keys($days));
        $this->assertSame([], $days[1]);
        $this->assertSame([['start' => '20:00', 'end' => '21:30']], $days[2]);
        $this->assertCount(4, $days[7]);
    }

    public function test_blocked_slot_counts_as_booked_and_can_be_freed(): void
    {
        $user = $this->finance();
        $user->givePermissionTo('masterdata.manage');
        $date = '2026-09-22'; // a Tuesday, evening slot by default

        $this->actingAs($user)->post(route('masterdata.blocked.store'), ['date' => $date, 'start_time' => '20:00', 'end_time' => '21:30', 'reason' => 'Other work'])
            ->assertSessionHasNoErrors();

        $this->assertContains('2026-09-22|20:00-21:30', BookedSlots::keys());
        $this->assertTrue(BookedSlots::conflicts($date, '20:00', '21:30'));

        $this->actingAs($user)->post(route('masterdata.blocked.store'), ['date' => $date, 'start_time' => '20:00', 'end_time' => '21:30'])
            ->assertSessionHasErrors('date');

        $this->actingAs($user)->delete(route('masterdata.blocked.destroy', \App\Models\BlockedSlot::firstOrFail()))->assertRedirect();
        $this->assertNotContains('2026-09-22|20:00-21:30', BookedSlots::keys());
    }

    public function test_only_operating_slots_can_be_blocked(): void
    {
        $user = $this->finance();
        $user->givePermissionTo('masterdata.manage');

        $this->actingAs($user)->post(route('masterdata.blocked.store'), ['date' => '2026-09-22', 'start_time' => '10:00', 'end_time' => '11:00'])
            ->assertSessionHasErrors('date');
    }

    public function test_dashboard_and_hours_page_render_the_calendar(): void
    {
        $user = $this->finance();
        $user->givePermissionTo(['masterdata.manage']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('availabilityCalendar', false);
        $this->actingAs($user)->get(route('masterdata.hours.edit'))->assertOk()->assertSee('Block slots');
    }
}
