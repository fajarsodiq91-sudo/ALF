<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TrainingMeetingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MeetingAttendanceOnDoneTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-10-17 13:00:00');
        $this->finance = User::factory()->create();
        $this->finance->assignRole('Finance');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function meeting(array $attrs = [], int $participants = 0): TrainingSessionMeeting
    {
        $session = TrainingSession::factory()->create(['status' => 'ongoing']);
        $session->participants()->attach(Customer::factory()->count($participants)->create());

        return TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id,
            'meeting_date' => '2026-10-17',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'is_completed' => false,
            ...$attrs,
        ]);
    }

    public function test_a_one_person_session_is_marked_present_when_done(): void
    {
        $meeting = $this->meeting();

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();

        $row = $meeting->attendances()->sole();
        $this->assertSame($meeting->session->customer_id, $row->customer_id);
        $this->assertSame('auto', $row->method);
        $this->assertSame('2026-10-17 09:00:00', $row->checked_in_at->toDateTimeString());
    }

    public function test_a_group_only_gets_the_ticked_people_marked_present(): void
    {
        $meeting = $this->meeting(participants: 3);
        [$a, $b, $c] = $meeting->session->participants;

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting), ['present' => [$a->id, $c->id]])->assertRedirect();

        $this->assertEqualsCanonicalizing([$a->id, $c->id], $meeting->attendances()->pluck('customer_id')->all());
        $this->assertTrue($meeting->fresh()->is_completed);
    }

    public function test_ids_outside_the_group_are_ignored(): void
    {
        $meeting = $this->meeting(participants: 2);
        $stranger = Customer::factory()->create();

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting), ['present' => [$stranger->id]])->assertRedirect();

        $this->assertSame(0, $meeting->attendances()->count());
    }

    public function test_existing_taps_are_kept_and_the_time_is_never_after_the_done_moment(): void
    {
        Carbon::setTestNow('2026-10-17 08:00:00');
        $meeting = $this->meeting(['start_time' => '09:00:00'], participants: 2);
        [$a, $b] = $meeting->session->participants;
        $tap = TrainingMeetingAttendance::create(['training_session_meeting_id' => $meeting->id, 'customer_id' => $a->id, 'checked_in_at' => '2026-10-17 08:55:00', 'method' => 'tap']);

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting), ['present' => [$a->id, $b->id]])->assertRedirect();

        $this->assertSame('tap', $tap->fresh()->method);
        $this->assertSame(2, $meeting->attendances()->count());
        $this->assertSame('2026-10-17 08:00:00', $meeting->attendances()->where('customer_id', $b->id)->first()->checked_in_at->toDateTimeString());
    }

    public function test_undo_removes_only_the_marks_made_by_done(): void
    {
        $meeting = $this->meeting(participants: 2);
        [$a, $b] = $meeting->session->participants;
        TrainingMeetingAttendance::create(['training_session_meeting_id' => $meeting->id, 'customer_id' => $a->id, 'checked_in_at' => now(), 'method' => 'manual']);

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting), ['present' => [$b->id]]);
        $this->assertSame(2, $meeting->attendances()->count());

        $this->actingAs($this->finance)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();

        $this->assertFalse($meeting->fresh()->is_completed);
        $this->assertSame([$a->id], $meeting->attendances()->pluck('customer_id')->all());
    }

    public function test_the_done_form_lists_group_participants_to_tick(): void
    {
        $meeting = $this->meeting(participants: 2);
        $name = $meeting->session->participants->first()->name;

        $this->actingAs($this->finance)->get(route('training.edit', $meeting->session))->assertOk()->assertSee('Who attended?')->assertSee($name);
    }
}
