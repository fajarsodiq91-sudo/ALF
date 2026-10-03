<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CertificateIssuer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CertificateTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function certificate(?CertificateTemplate $template = null): Certificate
    {
        $customer = Customer::factory()->create();
        $customer->forceFill(['customer_code' => '261001', 'must_change_password' => false])->save();
        $program = TrainingProgram::factory()->create(['program_type' => 'learning', 'certificate_template_id' => $template?->id]);
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'training_program_id' => $program->id, 'status' => 'completed']);
        CertificateIssuer::issueFor($session);

        return Certificate::firstOrFail();
    }

    private function template(bool $default): CertificateTemplate
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificate-templates/a.png', 'png');

        return CertificateTemplate::create(['name' => 'Gold', 'background_path' => 'certificate-templates/a.png', 'is_default' => $default]);
    }

    public function test_default_template_is_laid_over_with_the_certificate_data(): void
    {
        $template = $this->template(true);
        $certificate = $this->certificate();

        $this->actingAs($certificate->customer, 'customer')->get(route('portal.certificates.show', $certificate))->assertOk()
            ->assertSee('certificate-templates/a.png')
            ->assertSee($certificate->number)
            ->assertSee('261001')
            ->assertSee('Great Vibes');
        $this->assertSame($template->id, CertificateTemplate::forProgram($certificate->session->program)->id);
    }

    public function test_program_template_wins_over_the_default(): void
    {
        $this->template(true);
        $own = CertificateTemplate::create(['name' => 'Blue', 'background_path' => 'certificate-templates/a.png']);
        $certificate = $this->certificate($own);

        $this->assertSame($own->id, CertificateTemplate::forProgram($certificate->session->program)->id);
    }

    public function test_without_any_template_the_built_in_design_is_used(): void
    {
        $certificate = $this->certificate();

        $this->actingAs($certificate->customer, 'customer')->get(route('portal.certificates.show', $certificate))->assertOk()->assertDontSee('Great Vibes');
    }

    public function test_pdf_download_renders_with_a_template(): void
    {
        $this->template(true);
        $certificate = $this->certificate();

        $this->actingAs($certificate->customer, 'customer')->get(route('portal.certificates.download', $certificate))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_preview_and_delete_a_template(): void
    {
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $template = $this->template(false);

        $this->actingAs($admin)->get(route('settings.certificate-templates.index'))->assertOk()->assertSee('Gold');
        $this->actingAs($admin)->get(route('settings.certificate-templates.preview', $template))->assertOk()->assertSee('Nama Penerima Sertifikat');
        $this->actingAs($admin)->delete(route('settings.certificate-templates.destroy', $template))->assertRedirect();
        $this->assertDatabaseCount('certificate_templates', 0);
        Storage::disk('public')->assertMissing('certificate-templates/a.png');
    }
}
