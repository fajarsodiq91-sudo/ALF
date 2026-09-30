<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationReceived;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerRegistrationStatusTest extends TestCase
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

    /** Registers a customer through the public form and returns them. */
    private function registered(string $name = 'PT Status'): Customer
    {
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $customer = Customer::latest('id')->firstOrFail();
        $this->post(route('customer-registration.store', $customer->registration_token), ['name' => $name, 'email' => 'status@pt.test', 'phone' => '0812']);

        return $customer->fresh();
    }

    /** @return array<string, mixed> */
    private function approvalPayload(TrainingProgram $program): array
    {
        return ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'online', 'fee' => 5000000, 'payment_plan' => 'installment',
            'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30'], ['meeting_date' => '2026-10-08', 'start_time' => '20:00', 'end_time' => '21:30']],
        ]]];
    }

    public function test_submitting_lands_on_the_status_page_showing_waiting_for_approval(): void
    {
        Mail::fake();
        $customer = $this->registered();

        $this->assertNotNull($customer->status_token);
        $this->get(route('customer-registration.status', $customer->status_token))->assertOk()
            ->assertSee('Waiting for approval')->assertSee('What happens next')->assertSee('Thank you, PT Status')->assertSee('status@pt.test')
            ->assertDontSee('Your customer ID');
    }

    public function test_the_same_page_shows_approved_with_the_customer_id_after_approval(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Excel Basic', 'duration_days' => 2, 'standard_price' => 5000000]);
        $customer = $this->registered();
        $url = route('customer-registration.status', $customer->status_token);
        $this->get($url)->assertSee('Waiting for approval');

        $this->actingAs($this->finance(), 'web')->post(route('sales.approve', $customer), $this->approvalPayload($program))->assertRedirect(route('sales.show', $customer));
        $customer->refresh();

        $this->get($url)->assertOk()
            ->assertSee('Approved')->assertSee('Welcome, PT Status!')
            ->assertDontSee('Waiting for approval')->assertDontSee('What happens next')
            ->assertSee('Your customer ID')->assertSee($customer->customer_code)
            ->assertSee(route('portal.login'), false)->assertSee('Log in to your customer portal')
            ->assertSee('Approval email sent')
            ->assertSee('Excel Basic')->assertSee('Tue, 06 Oct 2026, 20:00 – 21:30')
            ->assertSee('Fee Rp 5.000.000')->assertSee('Down payment (50%): Rp 2.500.000')->assertSee('Final payment (50%): Rp 2.500.000');
    }

    public function test_the_page_never_shows_the_initial_password(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['duration_days' => 2]);
        $customer = $this->registered();
        $this->actingAs($this->finance(), 'web')->post(route('sales.approve', $customer), $this->approvalPayload($program));
        $customer->refresh();

        $html = $this->get(route('customer-registration.status', $customer->status_token))->getContent();

        $this->assertStringNotContainsString('Password:', $html);
        $this->assertSame(1, substr_count($html, 'font-mono text-3xl'));
        $this->assertStringContainsString('initial password was sent to your email', $html);
    }

    public function test_a_rejected_registration_says_so_with_the_reason(): void
    {
        Mail::fake();
        $customer = $this->registered();

        $this->actingAs($this->finance(), 'web')->post(route('sales.reject', $customer), ['rejection_reason' => 'Data tidak lengkap']);

        $this->get(route('customer-registration.status', $customer->status_token))->assertOk()
            ->assertSee('Not approved')->assertSee('unable to approve')->assertSee('Data tidak lengkap')
            ->assertDontSee('Waiting for approval')->assertDontSee('Your customer ID');
    }

    public function test_a_rejected_registration_without_a_reason_does_not_show_a_reason_line(): void
    {
        Mail::fake();
        $customer = $this->registered();

        $this->actingAs($this->finance(), 'web')->post(route('sales.reject', $customer));

        $this->get(route('customer-registration.status', $customer->status_token))->assertSee('Not approved')->assertDontSee('Reason:');
    }

    public function test_the_link_works_from_any_browser_not_only_the_one_that_registered(): void
    {
        Mail::fake();
        $customer = $this->registered();
        $this->flushSession();

        $this->get(route('customer-registration.status', $customer->status_token))->assertOk()->assertSee('Waiting for approval');
    }

    public function test_unknown_tokens_are_not_found(): void
    {
        $this->get(route('customer-registration.status', 'not-a-real-token'))->assertNotFound()->assertSee('no longer valid');
    }

    public function test_invitations_that_were_never_submitted_have_no_status_page(): void
    {
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);

        $this->assertNull(Customer::firstOrFail()->status_token);
        $this->get(route('customer-registration.status', Customer::firstOrFail()->registration_token))->assertNotFound();
    }

    public function test_the_old_done_address_follows_a_registration_made_in_the_same_browser(): void
    {
        Mail::fake();
        $customer = $this->registered();

        $this->get(route('customer-registration.done'))->assertRedirect(route('customer-registration.status', $customer->status_token));
    }

    public function test_the_old_done_address_shows_approval_after_the_customer_was_approved(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['duration_days' => 2]);
        $customer = $this->registered();
        $this->actingAs($this->finance(), 'web')->post(route('sales.approve', $customer), $this->approvalPayload($program));

        $this->followingRedirects()->get(route('customer-registration.done'))->assertSee('Approved')->assertSee($customer->fresh()->customer_code)->assertDontSee('Waiting for approval');
    }

    public function test_the_confirmation_email_links_to_the_status_page(): void
    {
        Mail::fake();
        $customer = $this->registered();

        Mail::assertSent(CustomerRegistrationReceived::class, function (CustomerRegistrationReceived $mail) use ($customer) {
            $mail->assertSeeInHtml('Check your registration status');
            $mail->assertSeeInHtml(route('customer-registration.status', $customer->status_token));

            return true;
        });
    }

    public function test_pending_status_lists_the_programs_the_customer_asked_for(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->create(['name' => 'Power BI Dasar', 'standard_price' => 7500000]);
        $this->actingAs($this->finance(), 'web')->post(route('sales.invite.store'), ['customer_type' => 'company']);
        $customer = Customer::firstOrFail();
        $this->post(route('customer-registration.store', $customer->registration_token), [
            'name' => 'PT Pilih', 'email' => 'pilih@pt.test', 'phone' => '0812', 'terms_accepted' => '1',
            'programs' => [['training_program_id' => $program->id, 'payment_plan' => 'full', 'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']]]],
        ]);

        $this->get(route('customer-registration.status', $customer->fresh()->status_token))
            ->assertSee('Programs and dates you asked for')->assertSee('Power BI Dasar')->assertSee('Tue, 06 Oct 2026, 20:00 – 21:30')->assertSee('Fee Rp 7.500.000');
    }
}
