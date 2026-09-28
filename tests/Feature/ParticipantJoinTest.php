<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParticipantJoinTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(?int $limit = 2, string $status = 'planned'): TrainingSession
    {
        $company = Customer::factory()->create(['customer_type' => 'company']);
        $session = TrainingSession::factory()->create(['customer_id' => $company->id, 'participant_limit' => $limit, 'status' => $status]);
        $session->syncParticipantToken();

        return $session->fresh();
    }

    private function join(TrainingSession $session, string $email = 'a@pt.test')
    {
        return $this->post(route('participant.store', $session->participant_token), ['name' => 'Budi', 'email' => $email, 'phone' => '0812']);
    }

    public function test_token_exists_only_when_a_limit_is_set(): void
    {
        $this->assertNotNull($this->makeSession(2)->participant_token);
        $this->assertNull($this->makeSession(null)->participant_token);
    }

    public function test_employee_joins_and_gets_a_portal_login_for_the_session(): void
    {
        $session = $this->makeSession();

        $this->get(route('participant.show', $session->participant_token))->assertOk()->assertSee($session->program->name);
        $this->join($session)->assertRedirect(route('participant.done'));

        $participant = Customer::where('email', 'a@pt.test')->firstOrFail();
        $this->assertSame($session->customer_id, $participant->company_customer_id);
        $this->assertNotNull($participant->customer_code);
        $this->assertTrue($participant->must_change_password);
        $this->assertCount(1, $session->participants);
        $this->assertSame(1, $session->fresh()->participants_count);

        $this->get(route('participant.done'))->assertOk()->assertSee($participant->customer_code);

        $this->post(route('portal.login.store'), ['customer_code' => $participant->customer_code, 'password' => $participant->customer_code]);
        $this->assertAuthenticatedAs($participant, 'customer');
        $this->assertTrue($participant->portalSessions()->whereKey($session->id)->exists());
    }

    public function test_the_limit_is_enforced_and_duplicate_emails_are_refused(): void
    {
        $session = $this->makeSession(2);

        $this->join($session, 'a@pt.test')->assertSessionHasNoErrors();
        $this->join($session, 'a@pt.test')->assertSessionHasErrors('name');
        $this->join($session, 'b@pt.test')->assertSessionHasNoErrors();
        $this->join($session, 'c@pt.test')->assertSessionHasErrors('name');

        $this->assertCount(2, $session->participants);
        $this->get(route('participant.show', $session->participant_token))->assertSee('closed or already full');
    }

    public function test_link_is_closed_for_cancelled_sessions_and_unknown_tokens(): void
    {
        $session = $this->makeSession(5, 'cancelled');
        $this->join($session)->assertSessionHasErrors('name');
        $this->get(route('participant.show', 'nope'))->assertNotFound();
    }

    public function test_participant_sees_the_session_but_not_the_payments_and_can_upload_a_project(): void
    {
        $session = $this->makeSession();
        $this->join($session);
        $participant = Customer::where('email', 'a@pt.test')->firstOrFail();
        $participant->forceFill(['must_change_password' => false])->save();

        $this->actingAs($participant, 'customer')->get(route('portal.dashboard'))->assertOk()
            ->assertSee($session->program->name)->assertDontSee('Payments');

        $this->actingAs($participant, 'customer')->post(route('portal.projects.store'), [
            'training_session_id' => $session->id, 'title' => 'Dashboard Excel', 'external_url' => 'https://example.com/x',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, CustomerProject::where('customer_id', $participant->id)->count());
    }

    public function test_company_sees_the_link_and_participants_in_its_portal(): void
    {
        $session = $this->makeSession();
        $this->join($session);
        $company = $session->customer;
        $company->update(['must_change_password' => false]);

        $this->actingAs($company, 'customer')->get(route('portal.dashboard'))->assertOk()
            ->assertSee(route('participant.show', $session->participant_token))->assertSee('Budi');
    }

    public function test_admin_can_set_the_limit_view_and_remove_participants(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $session = $this->makeSession(3);
        $this->join($session);
        $participant = Customer::where('email', 'a@pt.test')->firstOrFail();

        $this->actingAs($admin)->get(route('training.edit', $session))->assertOk()
            ->assertSee(route('participant.show', $session->participant_token))->assertSee('Budi');

        $payload = ['training_program_id' => $session->training_program_id, 'customer_id' => $session->customer_id, 'start_date' => '2026-10-06', 'end_date' => '2026-10-06', 'delivery_mode' => $session->delivery_mode, 'participants_count' => 1, 'fee' => 1000000, 'payment_plan' => 'full', 'status' => 'planned'];
        $this->actingAs($admin)->put(route('training.update', $session), [...$payload, 'participant_limit' => 0])->assertSessionHasErrors('participant_limit');

        $this->actingAs($admin)->delete(route('training.participants.destroy', [$session, $participant]))->assertRedirect();
        $this->assertCount(0, $session->participants()->get());
    }
}
