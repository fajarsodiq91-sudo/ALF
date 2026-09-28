<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationReceived;
use App\Models\Customer;
use App\Models\TrainingCategory;
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

class CustomerRequestedProgramsTest extends TestCase
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

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    private function token(): string
    {
        $this->actingAs($this->financeUser())->post(route('sales.invite.store'), ['customer_type' => 'company']);

        return Customer::firstOrFail()->registration_token;
    }

    /** @return array<string, mixed> */
    private function base(array $extra = []): array
    {
        return ['name' => 'PT Pemilih', 'email' => 'pemilih@pt.test', 'phone' => '0812', ...$extra];
    }

    /** @return array<string, mixed> */
    private function choice(TrainingProgram $program, array $meetings): array
    {
        return ['programs' => [['training_program_id' => $program->id, 'meetings' => $meetings]]];
    }

    /** @return array<string, string> */
    private function slot(string $date, string $start, string $end): array
    {
        return ['meeting_date' => $date, 'start_time' => $start, 'end_time' => $end];
    }

    public function test_form_lists_active_programs_by_category_and_the_available_days(): void
    {
        $dataAnalyst = TrainingCategory::factory()->create(['name' => 'Data Analyst']);
        $operatingKomputer = TrainingCategory::factory()->create(['name' => 'Operating Komputer']);
        TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'training_category_id' => $dataAnalyst->id]);
        TrainingProgram::factory()->create(['name' => 'Komputer Basic', 'training_category_id' => $operatingKomputer->id]);
        TrainingProgram::factory()->create(['name' => 'Belum Dikategorikan']);
        TrainingProgram::factory()->create(['name' => 'Program Nonaktif', 'is_active' => false]);
        $token = $this->token();

        $this->get(route('customer-registration.show', $token))->assertOk()
            ->assertSee('Programs you would like to take')
            ->assertSee('Power BI Dasar')->assertSee('Komputer Basic')
            ->assertSee('Data Analyst')->assertSee('Operating Komputer')->assertSee('Lainnya')
            ->assertDontSee('Program Nonaktif')
            ->assertSee('Our operating hours')->assertSee('"start":"20:00","end":"21:30"', false)
            ->assertSee('window.operatingHours', false)->assertSee('window.bookedSlots', false);
    }

    public function test_form_groups_programs_without_a_category_under_lainnya(): void
    {
        TrainingProgram::factory()->create(['name' => 'Excel Basic']);
        $token = $this->token();

        $this->get(route('customer-registration.show', $token))->assertOk()
            ->assertSeeInOrder(['<optgroup label="Lainnya">', 'Excel Basic'], false);
    }

    public function test_customer_picks_programs_and_dates_and_they_are_saved(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'duration_days' => 2]);
        $second = TrainingProgram::factory()->create(['name' => 'Excel Lanjut']);
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), $this->base(['programs' => [
            ['training_program_id' => $program->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30'), $this->slot('2026-10-10', '09:00', '10:30')]],
            ['training_program_id' => $second->id, 'meetings' => [$this->slot('2026-10-11', '14:40', '16:00')]],
        ]]))->assertRedirect();

        $customer = Customer::firstOrFail();
        $this->assertTrue($customer->isPendingApproval());
        $this->assertSame([
            ['training_program_id' => $program->id, 'payment_plan' => 'full', 'meetings' => [
                ['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30'],
                ['meeting_date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:30'],
            ]],
            ['training_program_id' => $second->id, 'payment_plan' => 'full', 'meetings' => [['meeting_date' => '2026-10-11', 'start_time' => '14:40', 'end_time' => '16:00']]],
        ], $customer->requested_programs);
        $this->assertSame(0, TrainingSession::count()); // nothing is scheduled until the company approves

        Mail::assertSent(CustomerRegistrationReceived::class, function (CustomerRegistrationReceived $mail) {
            $mail->assertSeeInHtml('Programs and dates you asked for');
            $mail->assertSeeInHtml('Power BI Dasar');
            $mail->assertSeeInHtml('Tue, 06 Oct 2026, 20:00 – 21:30');
            $mail->assertSeeInHtml('Excel Lanjut');

            return true;
        });

        $this->get(route('customer-registration.status', Customer::firstOrFail()->status_token))
            ->assertSee('Programs and dates you asked for')->assertSee('Power BI Dasar')->assertSee('Sat, 10 Oct 2026, 09:00 – 10:30')->assertSee('Excel Lanjut');
    }

    public function test_form_tells_the_browser_how_many_meetings_each_program_has(): void
    {
        $program = TrainingProgram::factory()->create(['name' => 'Excel Basic', 'duration_days' => 6]);
        $token = $this->token();

        $this->get(route('customer-registration.show', $token))->assertOk()
            ->assertSee('"'.$program->id.'":6', false)
            ->assertSee('Choose a date and time slot for each one')
            ->assertDontSee('Add another date')
            ->assertDontSee('Remove this date');
    }

    public function test_customer_must_choose_exactly_as_many_meetings_as_the_program_has(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Excel Basic', 'duration_days' => 3]);
        $token = $this->token();
        $post = fn (array $meetings) => $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, $meetings)));
        $dates = [$this->slot('2026-10-06', '20:00', '21:30'), $this->slot('2026-10-08', '20:00', '21:30'), $this->slot('2026-10-13', '20:00', '21:30')];

        $post(array_slice($dates, 0, 2))->assertSessionHasErrors('programs.0.meetings');
        $post([...$dates, $this->slot('2026-10-15', '20:00', '21:30')])->assertSessionHasErrors('programs.0.meetings');
        $post([$dates[0], $dates[1], $dates[1]])->assertSessionHasErrors('programs.0.meetings'); // same date and time twice
        $this->assertTrue(Customer::firstOrFail()->isAwaitingCustomer());

        $post($dates)->assertSessionHasNoErrors();
        $this->assertCount(3, Customer::firstOrFail()->requested_programs[0]['meetings']);
    }

    public function test_the_error_names_the_program_and_the_expected_count(): void
    {
        $program = TrainingProgram::factory()->create(['name' => 'Excel Basic', 'duration_days' => 6]);
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, [$this->slot('2026-10-06', '20:00', '21:30')])))
            ->assertSessionHasErrors(['programs.0.meetings' => 'Excel Basic has 6 meeting(s); please choose a date and time for all 6 (you chose 1).']);
    }

    public function test_the_same_day_can_hold_two_different_slots(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['duration_days' => 2]);
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, [
            $this->slot('2026-10-10', '09:00', '10:30'), $this->slot('2026-10-10', '10:40', '12:10'),
        ])))->assertSessionHasNoErrors();
    }

    public function test_program_form_calls_the_field_number_of_meetings(): void
    {
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('training.programs.create'))->assertOk()->assertSee('Number of meetings')->assertDontSee('Duration (days)');
        TrainingProgram::factory()->create(['name' => 'Excel Basic', 'duration_days' => 6]);
        $this->actingAs($finance)->get(route('training.programs.index'))->assertSee('Meetings')->assertDontSee('day(s)');
    }

    public function test_review_page_knows_each_programs_meeting_count(): void
    {
        $program = TrainingProgram::factory()->create(['duration_days' => 6]);
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->financeUser())->get(route('sales.review', $customer))->assertOk()
            ->assertSee('"meetings":6', false)->assertSee('meeting(s);', false);
    }

    public function test_choosing_programs_is_optional(): void
    {
        Mail::fake();
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), $this->base())->assertRedirect();

        $customer = Customer::firstOrFail();
        $this->assertNull($customer->requested_programs);
        $this->assertSame([], $customer->requestedProgramSummaries());
        $this->get(route('customer-registration.status', Customer::firstOrFail()->status_token))->assertDontSee('Programs and dates you asked for');
        Mail::assertSent(CustomerRegistrationReceived::class, function (CustomerRegistrationReceived $mail) {
            $mail->assertDontSeeInHtml('Programs and dates you asked for');

            return true;
        });
    }

    public function test_choices_are_validated(): void
    {
        $program = TrainingProgram::factory()->create();
        $inactive = TrainingProgram::factory()->create(['is_active' => false]);
        $token = $this->token();
        $post = fn (array $extra) => $this->post(route('customer-registration.store', $token), $this->base($extra));

        $post($this->choice($inactive, [$this->slot('2026-10-06', '20:00', '21:30')]))->assertSessionHasErrors('programs.0.training_program_id');
        $post(['programs' => [['training_program_id' => 9999, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]])->assertSessionHasErrors('programs.0.training_program_id');
        $post(['programs' => [['training_program_id' => '', 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]]]])->assertSessionHasErrors('programs.0.training_program_id');
        $post($this->choice($program, []))->assertSessionHasErrors('programs.0.meetings');
        $post($this->choice($program, [$this->slot('2026-09-01', '20:00', '21:30')]))->assertSessionHasErrors('programs.0.meetings.0.meeting_date'); // in the past
        $post($this->choice($program, [$this->slot('2026-10-05', '20:00', '21:30')]))->assertSessionHasErrors('programs.0.meetings.0.meeting_date'); // Monday: closed
        $post($this->choice($program, [$this->slot('2026-10-06', '19:00', '20:30')]))->assertSessionHasErrors('programs.0.meetings.0.meeting_date'); // not a slot
        $post($this->choice($program, [['meeting_date' => '2026-10-06']]))->assertSessionHasErrors('programs.0.meetings.0.meeting_date'); // no slot chosen
        $post(['programs' => array_fill(0, 6, $this->choice($program, [$this->slot('2026-10-06', '20:00', '21:30')])['programs'][0])])->assertSessionHasErrors('programs');

        $this->assertTrue(Customer::firstOrFail()->isAwaitingCustomer()); // still open: none of these went through
    }

    public function test_dates_outside_the_hours_are_allowed_when_the_company_turns_enforcement_off(): void
    {
        Mail::fake();
        OperatingHours::save(OperatingHours::defaults(), false);
        $program = TrainingProgram::factory()->create();
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, [['meeting_date' => '2026-10-05', 'start_time' => '14:00', 'end_time' => '15:00']])))
            ->assertSessionHasNoErrors();

        $this->assertNotNull(Customer::firstOrFail()->requested_programs);
    }

    public function test_review_page_is_prefilled_with_the_customers_choices(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'program_type' => 'consulting', 'standard_price' => 7500000]);
        $token = $this->token();
        $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, [$this->slot('2026-10-10', '09:00', '10:30')])));
        $customer = Customer::firstOrFail();

        $response = $this->actingAs($this->financeUser())->get(route('sales.review', $customer));

        $response->assertOk()
            ->assertSee('Requested by the customer')
            ->assertSee('Sat, 10 Oct 2026, 09:00 – 10:30')
            ->assertSee('"training_program_id":"'.$program->id.'"', false)
            ->assertSee('"meeting_date":"2026-10-10"', false)
            ->assertSee('"fee":"7500000"', false)
            ->assertSee('"program_type":"consulting"', false);
        $this->assertCount(1, $response->viewData('requested'));

        $this->actingAs($this->financeUser())->get(route('sales.show', $customer))->assertSee('Requested by the customer')->assertSee('Power BI Dasar');
    }

    public function test_review_page_without_choices_starts_with_one_empty_program(): void
    {
        $customer = Customer::factory()->pendingApproval()->create();
        TrainingProgram::factory()->create();

        $this->actingAs($this->financeUser())->get(route('sales.review', $customer))->assertOk()->assertDontSee('Requested by the customer');
    }

    public function test_approving_the_prefilled_choices_schedules_them(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $token = $this->token();
        $this->post(route('customer-registration.store', $token), $this->base($this->choice($program, [$this->slot('2026-10-10', '09:00', '10:30')])));
        $customer = Customer::firstOrFail();

        $this->actingAs($this->financeUser())->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'fee' => 5000000,
            'meetings' => [array_merge($this->slot('2026-10-10', '09:00', '10:30'), ['location' => '', 'topic' => ''])],
        ]]])->assertRedirect(route('sales.show', $customer));

        $session = TrainingSession::firstOrFail();
        $this->assertSame($program->id, $session->training_program_id);
        $this->assertSame('2026-10-10', $session->meetings->first()->meeting_date->toDateString());
        $this->assertSame([['training_program_id' => $program->id, 'payment_plan' => 'full', 'meetings' => [$this->slot('2026-10-10', '09:00', '10:30')]]], $customer->fresh()->requested_programs);
    }

    public function test_programs_that_disappear_are_left_out_of_the_summary(): void
    {
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create([
            'requested_programs' => [
                ['training_program_id' => $program->id, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]],
                ['training_program_id' => 99999, 'meetings' => [$this->slot('2026-10-06', '20:00', '21:30')]],
            ],
        ]);

        $this->assertCount(1, $customer->requestedProgramSummaries());
    }
}
