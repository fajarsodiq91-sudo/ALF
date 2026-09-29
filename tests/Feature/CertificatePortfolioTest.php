<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Services\CertificateIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificatePortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function certificate(array $sessionOverrides = []): Certificate
    {
        $customer = Customer::factory()->create();
        $customer->forceFill(['customer_code' => '260901'])->save();
        $program = TrainingProgram::factory()->create(['program_type' => 'learning']);
        $session = TrainingSession::factory()->create([
            'customer_id' => $customer->id,
            'training_program_id' => $program->id,
            'status' => 'completed',
            ...$sessionOverrides,
        ]);
        CertificateIssuer::issueFor($session);

        return Certificate::firstOrFail();
    }

    public function test_certificate_can_be_downloaded_as_a_pdf(): void
    {
        $certificate = $this->certificate();

        $response = $this->actingAs($certificate->customer, 'customer')
            ->get(route('portal.certificates.download', $certificate));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    public function test_pdf_download_is_blocked_for_someone_elses_certificate(): void
    {
        $certificate = $this->certificate();
        $other = Customer::factory()->create();

        $this->actingAs($other, 'customer')
            ->get(route('portal.certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_the_signature_block_uses_the_sessions_instructor(): void
    {
        $instructor = Employee::factory()->create(['name' => 'Fajar Sodiq', 'position' => 'Lead Trainer']);
        $certificate = $this->certificate(['instructor_id' => $instructor->id]);

        $this->actingAs($certificate->customer, 'customer')
            ->get(route('portal.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Fajar Sodiq')
            ->assertSee('Lead Trainer');
    }

    public function test_the_signature_block_falls_back_to_the_default_signer_without_an_instructor(): void
    {
        Setting::put(['certificate_signer_name' => 'Dewi Lestari', 'certificate_signer_title' => 'Training Director']);
        $certificate = $this->certificate(['instructor_id' => null]);

        $this->actingAs($certificate->customer, 'customer')
            ->get(route('portal.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertSee('Training Director');
    }

    public function test_viewing_the_certificate_creates_the_customers_portfolio_link_and_renders_a_qr_code(): void
    {
        $certificate = $this->certificate();
        $this->assertNull($certificate->customer->portfolio_token);

        $this->actingAs($certificate->customer, 'customer')
            ->get(route('portal.certificates.show', $certificate))
            ->assertOk()
            ->assertSee('<svg', false);

        // The QR encodes the URL as pixels, not as a link, so we check the token it was built from instead.
        $token = $certificate->customer->fresh()->portfolio_token;
        $this->assertNotNull($token);
        $this->get(route('customer-portfolio.show', $token))->assertOk()->assertSee($certificate->customer->name);
    }

    public function test_public_portfolio_shows_certificates_and_showcased_projects_only(): void
    {
        $certificate = $this->certificate();
        $customer = $certificate->customer;

        $shown = CustomerProject::factory()->create(['customer_id' => $customer->id, 'title' => 'Dashboard Penjualan', 'in_portfolio' => true, 'external_url' => 'https://example.com/demo']);
        CustomerProject::factory()->create(['customer_id' => $customer->id, 'title' => 'Draft Rahasia', 'in_portfolio' => false]);

        $response = $this->get(route('customer-portfolio.show', $customer->portfolioToken()));

        $response->assertOk()
            ->assertSee($customer->name)
            ->assertSee($certificate->number)
            ->assertSee('Dashboard Penjualan')
            ->assertDontSee('Draft Rahasia');
    }

    public function test_public_portfolio_with_an_unknown_token_is_not_found(): void
    {
        $this->get(route('customer-portfolio.show', 'does-not-exist'))->assertNotFound();
    }

    public function test_portfolio_file_download_only_works_for_showcased_projects(): void
    {
        $customer = Customer::factory()->create();
        $shown = CustomerProject::factory()->create([
            'customer_id' => $customer->id, 'in_portfolio' => true,
            'file_path' => 'customer-projects/1/demo.pdf', 'file_name' => 'demo.pdf',
        ]);
        $hidden = CustomerProject::factory()->create([
            'customer_id' => $customer->id, 'in_portfolio' => false,
            'file_path' => 'customer-projects/1/secret.pdf', 'file_name' => 'secret.pdf',
        ]);
        Storage::disk('local')->put($shown->file_path, 'demo content');
        Storage::disk('local')->put($hidden->file_path, 'secret content');

        $this->get(route('customer-portfolio.projects.download', $shown))->assertOk();
        $this->get(route('customer-portfolio.projects.download', $hidden))->assertNotFound();
    }
}
