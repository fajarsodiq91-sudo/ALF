<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\TapDevice;
use App\Models\TrainingMeetingAttendance;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TapAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        [, $this->token] = TapDevice::register('Lobby', 'Front door');
        $this->travelTo(now()->setTime(9, 0));
    }

    private function tap(array $body, ?string $token = null)
    {
        return $this->withToken($token ?? $this->token)->postJson('/api/tap', $body);
    }

    private function meetingFor(Customer $customer, array $meeting = []): TrainingSessionMeeting
    {
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'status' => 'ongoing']);

        return TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id,
            'meeting_date' => today(),
            'start_time' => '09:30:00',
            'end_time' => '12:00:00',
            'is_completed' => false,
            ...$meeting,
        ]);
    }

    public function test_a_reader_needs_a_valid_active_token(): void
    {
        $this->postJson('/api/tap', ['uid' => 'AA'])->assertUnauthorized();
        $this->tap(['uid' => 'AA'], 'wrong')->assertUnauthorized();

        TapDevice::first()->update(['is_active' => false]);
        $this->tap(['uid' => 'AA'])->assertUnauthorized();
    }

    public function test_an_unknown_card_is_rejected(): void
    {
        $this->tap(['uid' => 'FFFF'])->assertStatus(422)->assertJson(['ok' => false, 'status' => 'unknown_card']);
    }

    public function test_employee_taps_in_then_out(): void
    {
        $employee = Employee::factory()->create(['status' => 'active', 'rfid_uid' => '04A1B2C3']);

        $this->tap(['uid' => '04:a1:b2:c3'])->assertOk()->assertJson(['status' => 'check_in', 'name' => $employee->name]);
        $record = AttendanceRecord::where('employee_id', $employee->id)->sole();
        $this->assertSame('present', $record->status);
        $this->assertStringStartsWith('09:00', $record->check_in);

        // Held on the reader: not a second event.
        $this->travelTo(now()->addSeconds(30));
        $this->tap(['uid' => '04A1B2C3'])->assertOk()->assertJson(['status' => 'duplicate']);

        $this->travelTo(now()->setTime(17, 5));
        $this->tap(['uid' => '04A1B2C3'])->assertOk()->assertJson(['status' => 'check_out']);
        $this->assertStringStartsWith('17:05', $record->fresh()->check_out);
        $this->assertSame(1, AttendanceRecord::count());
    }

    public function test_employee_who_is_on_leave_or_resigned_cannot_tap(): void
    {
        Employee::factory()->create(['status' => 'resigned', 'rfid_uid' => 'AAAA']);
        $onLeaveToday = Employee::factory()->create(['status' => 'active', 'rfid_uid' => 'BBBB']);
        AttendanceRecord::create(['employee_id' => $onLeaveToday->id, 'attendance_date' => today(), 'status' => 'leave']);

        $this->tap(['uid' => 'AAAA'])->assertStatus(422)->assertJson(['status' => 'not_allowed']);
        $this->tap(['uid' => 'BBBB'])->assertStatus(422)->assertJson(['status' => 'not_allowed']);
        $this->assertSame('leave', $onLeaveToday->attendanceRecords()->sole()->status);
    }

    public function test_employee_can_check_in_with_the_id_card_qr(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        $this->tap(['qr' => route('id-cards.verify', $employee->idCardToken())])->assertOk()->assertJson(['status' => 'check_in']);
        $this->assertSame(1, $employee->attendanceRecords()->count());
    }

    public function test_customer_is_marked_present_at_todays_meeting(): void
    {
        $customer = Customer::factory()->create(['rfid_uid' => 'CC11']);
        $meeting = $this->meetingFor($customer);

        $this->tap(['uid' => 'CC11'])->assertOk()->assertJson(['status' => 'attended']);
        $this->assertDatabaseHas('training_meeting_attendances', ['training_session_meeting_id' => $meeting->id, 'customer_id' => $customer->id, 'method' => 'rfid']);

        $this->tap(['uid' => 'CC11'])->assertOk()->assertJson(['status' => 'duplicate']);
        $this->assertSame(1, TrainingMeetingAttendance::count());
    }

    public function test_customer_can_attend_with_the_qr_code_and_as_a_participant(): void
    {
        $customer = Customer::factory()->create();
        $company = Customer::factory()->create();
        $meeting = $this->meetingFor($company);
        $meeting->session->participants()->attach($customer->id);

        $this->tap(['qr' => route('id-cards.verify', $customer->idCardToken())])->assertOk()->assertJson(['status' => 'attended']);
        $this->assertDatabaseHas('training_meeting_attendances', ['customer_id' => $customer->id, 'method' => 'qr']);
    }

    public function test_customer_without_a_meeting_now_is_told_why(): void
    {
        $noClass = Customer::factory()->create(['rfid_uid' => 'DD11']);
        $this->tap(['uid' => 'DD11'])->assertStatus(422)->assertJson(['status' => 'no_meeting']);

        $tooEarly = Customer::factory()->create(['rfid_uid' => 'EE11']);
        $this->meetingFor($tooEarly, ['start_time' => '15:00:00', 'end_time' => '17:00:00']);
        $this->tap(['uid' => 'EE11'])->assertStatus(422)->assertJson(['status' => 'outside_hours']);

        $this->assertSame(0, TrainingMeetingAttendance::count());
    }

    public function test_attendance_is_shown_on_the_session_meeting_schedule(): void
    {
        $customer = Customer::factory()->create(['rfid_uid' => 'FF11']);
        $meeting = $this->meetingFor($customer);
        $this->tap(['uid' => 'FF11'])->assertOk();

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignRole('Super Admin');
        $this->actingAs($admin->fresh())->get(route('training.edit', $meeting->session))->assertOk()->assertSee($customer->name);
    }

    public function test_admin_manages_devices_and_the_token_is_shown_once(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignRole('Super Admin');

        $this->actingAs($admin)->post(route('settings.tap-devices.store'), ['name' => 'Class A', 'location' => 'Room 1'])
            ->assertRedirect()->assertSessionHas('device_token');
        $device = TapDevice::where('name', 'Class A')->sole();
        $plain = session('device_token');
        $this->assertNotSame($plain, $device->token_hash);
        $this->tap(['uid' => 'ZZ'], $plain)->assertStatus(422);

        $this->actingAs($admin)->put(route('settings.tap-devices.update', $device), ['regenerate' => 1])->assertSessionHas('device_token');
        $this->tap(['uid' => 'ZZ'], $plain)->assertUnauthorized();
    }

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::factory()->create()->assignRole('Super Admin');
    }

    public function test_kiosk_scan_records_attendance_for_a_logged_in_staff_member(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);
        $admin = $this->admin();

        $this->get(route('kiosk.index'))->assertRedirect();
        $this->postJson(route('kiosk.scan'), ['qr' => 'x'])->assertUnauthorized();

        $this->actingAs($admin)->get(route('kiosk.index'))->assertOk()->assertSee('html5-qrcode', false);
        $this->actingAs($admin)->postJson(route('kiosk.scan'), ['qr' => route('id-cards.verify', $employee->idCardToken())])
            ->assertOk()->assertJson(['ok' => true, 'status' => 'check_in', 'name' => $employee->name]);
        $this->actingAs($admin)->postJson(route('kiosk.scan'), ['qr' => 'garbage'])->assertOk()->assertJson(['ok' => false, 'status' => 'unknown_card']);
    }

    public function test_kiosk_is_closed_to_staff_without_hr_or_training_manage(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create()->givePermissionTo('access-erp');

        $this->actingAs($user)->get(route('kiosk.index'))->assertForbidden();
    }

    public function test_session_report_shows_attendance_rate_per_participant(): void
    {
        $company = Customer::factory()->create();
        $present = Customer::factory()->create(['rfid_uid' => 'AB01']);
        $absent = Customer::factory()->create();
        $session = TrainingSession::factory()->create(['customer_id' => $company->id, 'status' => 'ongoing']);
        $session->participants()->attach([$present->id, $absent->id]);
        foreach ([today()->subDay(), today()] as $date) {
            TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => $date, 'start_time' => '09:30:00', 'end_time' => '12:00:00', 'is_completed' => false]);
        }
        TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'meeting_date' => today()->addWeek(), 'is_completed' => false]);

        $this->tap(['uid' => 'AB01'])->assertOk()->assertJson(['status' => 'attended']);

        $response = $this->actingAs($this->admin())->get(route('training.attendance', $session))->assertOk();
        $response->assertSeeInOrder([$absent->name, '0/2', '0%']);
        $response->assertSeeInOrder([$present->name, '1/2', '50%']);
        // The company that owns the session is not a participant, so it is not listed.
        $response->assertDontSee($company->name.'</span>', false);
    }

    public function test_staff_can_mark_and_unmark_attendance_by_hand(): void
    {
        $customer = Customer::factory()->create();
        $meeting = $this->meetingFor($customer);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('training.attendance.toggle', [$meeting, $customer]))->assertRedirect();
        $this->assertDatabaseHas('training_meeting_attendances', ['customer_id' => $customer->id, 'method' => 'manual']);

        $this->actingAs($admin)->patch(route('training.attendance.toggle', [$meeting, $customer]));
        $this->assertSame(0, TrainingMeetingAttendance::count());
    }
}
