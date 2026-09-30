<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\OperatingHours;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OperatingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 10:00:00'); // a Tuesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @return array<string, mixed> */
    private function meeting(string $date, ?string $start, ?string $end): array
    {
        return ['meeting_date' => $date, 'start_time' => $start, 'end_time' => $end];
    }

    private function postMeeting(TrainingSession $session, array $meeting)
    {
        return $this->actingAs($this->user('Finance'))->post(route('training.meetings.store', $session), $meeting);
    }

    public function test_default_schedule_matches_the_company_hours(): void
    {
        $saturday = [['09:00', '10:30', false], ['10:40', '12:10', false], ['13:00', '14:30', false], ['14:40', '16:00', false]];

        $this->assertSame([2 => [['20:00', '21:30', false]], 4 => [['20:00', '21:30', false]], 6 => $saturday, 7 => $saturday], OperatingHours::schedule());
        $this->assertTrue(OperatingHours::enforced());
        $this->assertSame('20:00 – 21:30', OperatingHours::formatted()['Tuesday']);
        $this->assertSame('09:00 – 10:30, 10:40 – 12:10, 13:00 – 14:30, 14:40 – 16:00', OperatingHours::formatted()['Sunday']);
        $this->assertArrayNotHasKey('Monday', OperatingHours::formatted());
    }

    public function test_violation_rules(): void
    {
        $this->assertNull(OperatingHours::violation('2026-10-06', '20:00', '21:30')); // Tuesday
        $this->assertNull(OperatingHours::violation('2026-10-08', '20:00:00', '21:30:00')); // Thursday, DB-style times
        $this->assertNull(OperatingHours::violation('2026-10-10', '14:40', '16:00')); // Saturday, last slot
        $this->assertNull(OperatingHours::violation('2026-10-11', '10:40', '12:10')); // Sunday

        $this->assertStringContainsString('not an operating day', OperatingHours::violation('2026-10-05', '20:00', '21:30')); // Monday
        $this->assertStringContainsString('not an operating day', OperatingHours::violation('2026-10-09', '20:00', '21:30')); // Friday
        $this->assertStringContainsString('Choose a time slot', OperatingHours::violation('2026-10-06', null, null));
        $this->assertStringContainsString('must be one of the Tuesday slots', OperatingHours::violation('2026-10-06', '19:00', '20:30'));
        $this->assertStringContainsString('20:00 – 21:30', OperatingHours::violation('2026-10-06', '20:00', '21:00'));
        $this->assertStringContainsString('Saturday slots', OperatingHours::violation('2026-10-10', '10:30', '10:40')); // the break
        $this->assertStringContainsString('Saturday slots', OperatingHours::violation('2026-10-10', '20:00', '21:30')); // evening slot is not Saturday's
    }

    public function test_adding_a_meeting_is_enforced_on_the_training_session(): void
    {
        $session = TrainingSession::factory()->create();

        $this->postMeeting($session, $this->meeting('2026-10-05', '20:00', '21:30'))->assertSessionHasErrors('meeting_date');
        $this->postMeeting($session, $this->meeting('2026-10-06', '09:00', '10:30'))->assertSessionHasErrors('meeting_date');
        $this->postMeeting($session, $this->meeting('2026-10-06', null, null))->assertSessionHasErrors('meeting_date');
        $this->assertSame(0, $session->meetings()->count());

        $this->postMeeting($session, $this->meeting('2026-10-11', '13:00', '14:30'))->assertSessionHasNoErrors();
        $this->assertSame(1, $session->meetings()->count());
    }

    public function test_approval_rejects_meetings_outside_the_hours_and_names_the_meeting(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->user('Finance'))->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online',
            'meetings' => [
                $this->meeting('2026-10-06', '20:00', '21:30'),
                $this->meeting('2026-10-07', '20:00', '21:30'), // Wednesday
            ],
        ]]])->assertSessionHasErrors('programs.0.meetings.1.meeting_date');

        $this->assertTrue($customer->fresh()->isPendingApproval());
        $this->assertSame(0, TrainingSession::count());
        Mail::assertNothingSent();
    }

    public function test_turning_enforcement_off_allows_exceptions(): void
    {
        $session = TrainingSession::factory()->create();
        OperatingHours::save(OperatingHours::defaults(), false);

        $this->postMeeting($session, $this->meeting('2026-10-05', '14:00', '15:00'))->assertSessionHasNoErrors();
        $this->postMeeting($session, $this->meeting('2026-10-06', null, null))->assertSessionHasNoErrors();
        $this->assertSame(2, $session->meetings()->count());
    }

    public function test_admin_saves_operating_hours_in_settings(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->get(route('masterdata.hours.edit'))->assertOk()->assertSee('Operating Hours')->assertSee('Weekly schedule')->assertSee('Only allow meetings inside these hours');
        $this->actingAs($admin)->get(route('settings.system'))->assertOk()->assertDontSee('Only allow meetings inside these hours');

        $this->actingAs($admin)->put(route('masterdata.hours.update'), [
            'hours' => [
                '3' => [['start' => '18:00', 'end' => '19:00'], ['start' => '13:00', 'end' => '14:00']],
                '5' => [['start' => '09:00', 'end' => '10:00']],
            ],
            'enforced' => '1',
        ])->assertRedirect(route('masterdata.hours.edit'));

        $this->assertSame([3 => [['13:00', '14:00', false], ['18:00', '19:00', false]], 5 => [['09:00', '10:00', false]]], OperatingHours::schedule());
        $this->actingAs($admin)->get(route('masterdata.hours.edit'))->assertSee('"start":"13:00"', false)->assertSee('"start":"18:00"', false);
        $this->assertNull(OperatingHours::violation('2026-10-07', '13:00', '14:00')); // Wednesday is open now
        $this->assertNotNull(OperatingHours::violation('2026-10-06', '20:00', '21:30')); // Tuesday closed now

        $this->actingAs($admin)->put(route('masterdata.hours.update'), ['hours' => [], 'enforced' => '0']);
        $this->assertSame([], OperatingHours::schedule());
        $this->assertFalse(OperatingHours::enforced());
    }

    public function test_settings_validation(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->put(route('masterdata.hours.update'), ['hours' => ['2' => [['start' => '21:00', 'end' => '20:00']]]])
            ->assertSessionHasErrors('hours.2.0.end');
        $this->actingAs($admin)->put(route('masterdata.hours.update'), ['hours' => ['2' => [['start' => '', 'end' => '20:00']]]])
            ->assertSessionHasErrors('hours.2.0.start');
        $this->actingAs($admin)->put(route('masterdata.hours.update'), ['hours' => ['2' => [['start' => 'abc', 'end' => '20:00']]]])
            ->assertSessionHasErrors('hours.2.0.start');
        $this->actingAs($admin)->put(route('masterdata.hours.update'), ['hours' => ['9' => [['start' => '09:00', 'end' => '10:00']]]])
            ->assertSessionHasErrors('hours');

        $this->assertSame(OperatingHours::defaults(), OperatingHours::schedule()); // nothing was saved
    }

    public function test_only_master_data_admins_can_change_the_hours(): void
    {
        $this->actingAs($this->user('Finance'))->get(route('masterdata.hours.edit'))->assertForbidden();
        $this->actingAs($this->user('Finance'))->put(route('masterdata.hours.update'), ['hours' => []])->assertForbidden();
        $this->assertSame(OperatingHours::defaults(), OperatingHours::schedule());
    }

    public function test_pages_expose_the_slots_to_the_browser(): void
    {
        $customer = Customer::factory()->pendingApproval()->create();
        TrainingProgram::factory()->create();
        $session = TrainingSession::factory()->create();
        $finance = $this->user('Finance');

        foreach ([route('sales.review', $customer), route('training.edit', $session)] as $url) {
            $this->actingAs($finance)->get($url)->assertOk()->assertSee('window.operatingHours', false)->assertSee('"value":"20:00-21:30"', false)->assertSee('Select');
        }
    }

    public function test_customers_see_the_hours_in_the_portal_and_the_approval_email(): void
    {
        $customer = Customer::factory()->withPortalAccess('rahasia123')->create();
        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'rahasia123']);

        $this->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Our operating hours')->assertSee('weekDayNames', false)
            ->assertSee('"1":[]', false)->assertSee('"start":"20:00","end":"21:30"', false)->assertSee('"start":"14:40","end":"16:00"', false);

        Mail::fake();
        $pending = Customer::factory()->pendingApproval()->create();
        $program = TrainingProgram::factory()->create();
        $this->actingAs($this->user('Finance'), 'web')->post(route('sales.approve', $pending), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'meetings' => [$this->meeting('2026-10-13', '20:00', '21:30')],
        ]]]);

        Mail::assertSent(CustomerRegistrationApproved::class, function (CustomerRegistrationApproved $mail) {
            $mail->assertSeeInHtml('Our operating hours');
            $mail->assertSeeInHtml('Thursday');
            $mail->assertSeeInHtml('10:40 – 12:10');

            return true;
        });
    }
}
