<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CertificateIssuer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $code): Customer
    {
        $customer = Customer::factory()->create();
        $customer->forceFill(['customer_code' => $code, 'must_change_password' => false])->save();

        return $customer;
    }

    private function completed(Customer $customer, string $type = 'learning'): TrainingSession
    {
        $program = TrainingProgram::factory()->create(['program_type' => $type]);

        return TrainingSession::factory()->create(['customer_id' => $customer->id, 'training_program_id' => $program->id, 'status' => 'completed']);
    }

    public function test_number_follows_the_customer_id_and_ends_with_a_unique_code(): void
    {
        $customer = $this->customer('260901');
        $this->assertSame(1, CertificateIssuer::issueFor($this->completed($customer)));

        $certificate = Certificate::firstOrFail();
        $this->assertMatchesRegularExpression('#^ALF/LRN/2026/IX/001-(?=[A-Z0-9]*[A-Z])(?=[A-Z0-9]*\d)[A-Z0-9]{3}$#', $certificate->number);
        $this->assertSame(substr($certificate->number, -3), $certificate->suffix);

        $this->assertSame('ALF/LRN/2027/XII/012', CertificateIssuer::prefix($this->customer('271212')));
    }

    public function test_codes_never_repeat_across_certificates(): void
    {
        $customer = $this->customer('260901');
        for ($i = 0; $i < 60; $i++) {
            CertificateIssuer::issueFor($this->completed($customer));
        }

        $this->assertSame(60, Certificate::count());
        $this->assertSame(60, Certificate::distinct()->count('suffix'));
        $this->assertSame(60, Certificate::distinct()->count('number'));
    }

    public function test_only_completed_learning_sessions_are_certified_once(): void
    {
        $customer = $this->customer('260901');

        $planned = $this->completed($customer);
        $planned->update(['status' => 'planned']);
        $this->assertSame(0, CertificateIssuer::issueFor($planned));
        $this->assertSame(0, CertificateIssuer::issueFor($this->completed($customer, 'consulting')));

        $done = $this->completed($customer);
        $this->assertSame(1, CertificateIssuer::issueFor($done));
        $this->assertSame(0, CertificateIssuer::issueFor($done));
    }

    public function test_corporate_sessions_certify_the_participants_not_the_company(): void
    {
        $company = $this->customer('260801');
        $session = $this->completed($company);
        $session->update(['participant_limit' => 5]);
        $session->syncParticipantToken();
        $a = $this->customer('260902');
        $b = $this->customer('260903');
        $session->participants()->attach([$a->id, $b->id]);

        $this->assertSame(2, CertificateIssuer::issueFor($session->fresh()));
        $this->assertSame([$a->id, $b->id], Certificate::orderBy('customer_id')->pluck('customer_id')->all());
        $this->assertStringStartsWith('ALF/LRN/2026/IX/002-', Certificate::where('customer_id', $a->id)->value('number'));
    }

    public function test_completing_a_session_in_the_erp_issues_the_certificate_and_the_customer_can_open_it(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $customer = $this->customer('260901');
        $session = $this->completed($customer);
        $session->update(['status' => 'ongoing']);

        $this->actingAs($admin)->put(route('training.update', $session), [
            'training_program_id' => $session->training_program_id, 'customer_id' => $customer->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-02',
            'delivery_mode' => $session->delivery_mode, 'participants_count' => 1, 'fee' => $session->fee, 'payment_plan' => 'full', 'status' => 'completed',
        ])->assertRedirect(route('training.index'));

        $certificate = Certificate::firstOrFail();

        $this->actingAs($customer, 'customer')->get(route('portal.certificates.index'))->assertOk()->assertSee($certificate->number);
        $this->actingAs($customer, 'customer')->get(route('portal.certificates.show', $certificate))->assertOk()
            ->assertSee($customer->name)->assertSee($certificate->number)->assertSee('Certificate')->assertSee('of Completion');

        $other = $this->customer('260902');
        $this->actingAs($other, 'customer')->get(route('portal.certificates.show', $certificate))->assertForbidden();
    }
}
