<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Mail\CustomerRegistrationRejected;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerApprovalTest extends TestCase
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

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function approvalPayload(TrainingProgram $program, array $overrides = []): array
    {
        return [
            'programs' => [[
                'training_program_id' => $program->id,
                'delivery_mode' => 'hybrid',
                'location' => 'Kantor ALF, Bandung',
                'fee' => 5000000,
                'meetings' => [
                    ['meeting_date' => '2026-10-13', 'start_time' => '20:00', 'end_time' => '21:30', 'location' => '', 'topic' => 'Sesi lanjutan'],
                    ['meeting_date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:30', 'location' => 'Ruang Meeting A', 'topic' => 'Pengenalan'],
                ],
            ]],
            ...$overrides,
        ];
    }

    public function test_pending_customer_is_listed_with_a_review_action(): void
    {
        $pending = Customer::factory()->pendingApproval()->create(['name' => 'PT Menunggu']);
        Customer::factory()->create(['name' => 'PT Selesai']);
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.index'))->assertSee('Pending approval')->assertSee(route('sales.review', $pending), false);
        $this->actingAs($finance)->get(route('sales.index', ['status' => 'pending_approval']))->assertSee('PT Menunggu')->assertDontSee('PT Selesai');
        $this->assertNull($pending->customer_code);
    }

    public function test_review_page_renders_only_for_pending_customers(): void
    {
        TrainingProgram::factory()->create(['name' => 'Power BI Dasar']);
        $pending = Customer::factory()->pendingApproval()->create(['name' => 'PT Menunggu']);
        $done = Customer::factory()->create();
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.review', $pending))->assertOk()->assertSee('PT Menunggu')->assertSee('Power BI Dasar')->assertSee('Approve &amp; Send Email', false);
        $this->actingAs($finance)->get(route('sales.review', $done))->assertRedirect(route('sales.index'));
    }

    public function test_review_page_offers_program_types_and_lists_programs_with_their_type(): void
    {
        TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'program_type' => 'learning']);
        TrainingProgram::factory()->create(['name' => 'Strategi Data', 'program_type' => 'consulting']);
        TrainingProgram::factory()->create(['name' => 'Program Lama', 'is_active' => false]);
        $customer = Customer::factory()->pendingApproval()->create();

        $response = $this->actingAs($this->financeUser())->get(route('sales.review', $customer));

        $response->assertOk()
            ->assertSee('Program type')->assertSee('Learning')->assertSee('Consulting')
            ->assertSee('Power BI Dasar')->assertSee('Strategi Data')
            ->assertDontSee('Program Lama')
            ->assertDontSee('no active programs to choose from');
        $this->assertCount(2, $response->viewData('programs'));
        $this->assertSame(['learning' => 'Learning', 'consulting' => 'Consulting'], $response->viewData('programTypes'));
    }

    public function test_review_page_explains_an_empty_program_catalog(): void
    {
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->financeUser())->get(route('sales.review', $customer))
            ->assertOk()
            ->assertSee('no active programs to choose from')
            ->assertSee(route('training.programs.create'), false);
    }

    public function test_approving_assigns_id_password_programs_and_emails_the_customer(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'program_type' => 'consulting']);
        $customer = Customer::factory()->pendingApproval()->create(['name' => 'PT Menunggu', 'email' => 'pt@menunggu.test', 'city' => 'Bandung']);

        $this->actingAs($this->financeUser())->post(route('sales.approve', $customer), $this->approvalPayload($program))
            ->assertRedirect(route('sales.show', $customer))->assertSessionHas('status');

        $customer->refresh();
        $this->assertSame('260901', $customer->customer_code);
        $this->assertSame(Customer::REGISTRATION_COMPLETE, $customer->registration_status);
        $this->assertTrue(Hash::check('260901', $customer->password));
        $this->assertTrue($customer->must_change_password);
        $this->assertNotNull($customer->approved_at);

        $session = TrainingSession::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame($program->id, $session->training_program_id);
        $this->assertSame('hybrid', $session->delivery_mode);
        $this->assertSame('2026-10-10', $session->start_date->toDateString());
        $this->assertSame('2026-10-13', $session->end_date->toDateString());
        $this->assertSame(['2026-10-10', '2026-10-13'], $session->meetings->map(fn ($m) => $m->meeting_date->toDateString())->all());

        Mail::assertSent(CustomerRegistrationApproved::class, function (CustomerRegistrationApproved $mail) {
            $mail->assertTo('pt@menunggu.test');
            $mail->assertSeeInHtml('PT Menunggu');
            $mail->assertSeeInHtml('260901');
            $mail->assertSeeInHtml('Bandung');
            $mail->assertSeeInHtml('Power BI Dasar');
            $mail->assertSeeInHtml('Consulting');
            $mail->assertSeeInHtml('Ruang Meeting A');
            $mail->assertSeeInHtml('Kantor ALF, Bandung');
            $mail->assertSeeInHtml(route('portal.login'));

            return true;
        });
    }

    public function test_approval_ids_follow_the_approval_order(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $first = Customer::factory()->pendingApproval()->create();
        $second = Customer::factory()->pendingApproval()->create();
        $finance = $this->financeUser();

        $this->actingAs($finance)->post(route('sales.approve', $second), $this->approvalPayload($program));
        $this->actingAs($finance)->post(route('sales.approve', $first), $this->approvalPayload($program));

        $this->assertSame('260901', $second->fresh()->customer_code);
        $this->assertSame('260902', $first->fresh()->customer_code);
    }

    public function test_approval_needs_a_program_with_a_meeting(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $inactive = TrainingProgram::factory()->create(['is_active' => false]);
        $customer = Customer::factory()->pendingApproval()->create();
        $finance = $this->financeUser();

        $this->actingAs($finance)->post(route('sales.approve', $customer), [])->assertSessionHasErrors('programs');
        $this->actingAs($finance)->post(route('sales.approve', $customer), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'meetings' => [],
        ]]])->assertSessionHasErrors('programs.0.meetings');
        $this->actingAs($finance)->post(route('sales.approve', $customer), $this->approvalPayload($inactive))
            ->assertSessionHasErrors('programs.0.training_program_id');
        $this->actingAs($finance)->post(route('sales.approve', $customer), $this->approvalPayload($program, ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'bogus',
            'meetings' => [['meeting_date' => '2026-10-10', 'start_time' => '12:00', 'end_time' => '09:00']],
        ]]]))->assertSessionHasErrors(['programs.0.delivery_mode', 'programs.0.meetings.0.end_time']);

        $this->assertTrue($customer->fresh()->isPendingApproval());
        $this->assertNull($customer->fresh()->customer_code);
        Mail::assertNothingSent();
    }

    public function test_approval_keeps_working_when_the_email_cannot_be_sent(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->financeUser())->post(route('sales.approve', $customer), $this->approvalPayload($program))
            ->assertRedirect(route('sales.show', $customer))
            ->assertSessionHas('error');

        $this->assertSame('260901', $customer->fresh()->customer_code);
    }

    public function test_only_pending_customers_can_be_approved_or_rejected(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create();
        $done = Customer::factory()->create();
        $finance = $this->financeUser();

        $this->actingAs($finance)->post(route('sales.approve', $done), $this->approvalPayload($program))->assertSessionHas('error');
        $this->actingAs($finance)->post(route('sales.reject', $done))->assertSessionHas('error');
        $this->assertSame(0, TrainingSession::count());
        Mail::assertNothingSent();
    }

    public function test_rejecting_keeps_the_record_without_an_id_and_emails_the_customer(): void
    {
        Mail::fake();
        $customer = Customer::factory()->pendingApproval()->create(['name' => 'PT Ditolak', 'email' => 'tolak@pt.test']);

        $this->actingAs($this->financeUser())->post(route('sales.reject', $customer), ['rejection_reason' => 'Data tidak lengkap'])
            ->assertRedirect(route('sales.index'))->assertSessionHas('status');

        $customer->refresh();
        $this->assertTrue($customer->isRejected());
        $this->assertSame('Data tidak lengkap', $customer->rejection_reason);
        $this->assertNull($customer->customer_code);
        $this->assertNull($customer->password);
        $this->actingAs($this->financeUser())->get(route('sales.index', ['status' => 'rejected']))->assertSee('Rejected');

        Mail::assertSent(CustomerRegistrationRejected::class, function (CustomerRegistrationRejected $mail) {
            $mail->assertTo('tolak@pt.test');
            $mail->assertSeeInHtml('PT Ditolak');
            $mail->assertSeeInHtml('unable to approve');
            $mail->assertSeeInHtml('Data tidak lengkap');

            return true;
        });
    }

    public function test_rejection_email_works_without_a_reason(): void
    {
        Mail::fake();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->financeUser())->post(route('sales.reject', $customer));

        Mail::assertSent(CustomerRegistrationRejected::class, function (CustomerRegistrationRejected $mail) {
            $mail->assertDontSeeInHtml('Reason:');

            return true;
        });
    }

    public function test_rejection_stands_even_if_the_email_cannot_be_sent(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($this->financeUser())->post(route('sales.reject', $customer))
            ->assertRedirect(route('sales.index'))->assertSessionHas('error');

        $this->assertTrue($customer->fresh()->isRejected());
    }

    public function test_view_only_users_cannot_review_or_approve(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'sales.view']);
        $program = TrainingProgram::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create();

        $this->actingAs($viewer)->get(route('sales.review', $customer))->assertForbidden();
        $this->actingAs($viewer)->post(route('sales.approve', $customer), $this->approvalPayload($program))->assertForbidden();
        $this->actingAs($viewer)->post(route('sales.reject', $customer))->assertForbidden();
        $this->actingAs($viewer)->get(route('sales.show', $customer))->assertOk();
    }

    public function test_pending_and_rejected_customers_stay_out_of_other_modules(): void
    {
        Customer::factory()->pendingApproval()->create(['name' => 'PT Belum Disetujui']);
        Customer::factory()->create(['name' => 'PT Disetujui']);

        $customers = $this->actingAs($this->financeUser())->get(route('projects.create'))->viewData('customers');
        $this->assertSame(['PT Disetujui'], $customers->pluck('name')->all());
    }

    public function test_customer_detail_page_renders(): void
    {
        $customer = Customer::factory()->create();
        TrainingSession::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->financeUser())->get(route('sales.show', $customer))->assertOk()->assertSee($customer->customer_code);
    }
}
