<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use App\Services\CertificateIssuer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');
        Carbon::setTestNow('2026-09-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @var array<int, string> plain passwords of the customers created here, by id */
    private array $passwords = [];

    private function customer(?string $password = null, array $attributes = []): Customer
    {
        $customer = Customer::factory()->withPortalAccess($password)->create(['name' => 'PT Peserta', ...$attributes]);
        $this->passwords[$customer->id] = $password ?? $customer->customer_code;

        return $customer;
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    /** Logs in through the real portal form (actingAs would swap the app's default guard). */
    private function loginAs(Customer $customer): void
    {
        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => $this->passwords[$customer->id]])
            ->assertSessionDoesntHaveErrors();
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_customer_logs_in_with_id_as_username_and_password_and_must_change_it(): void
    {
        $customer = $this->customer();

        $this->get(route('portal.login'))->assertOk()->assertSee('Customer ID');
        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => $customer->customer_code])
            ->assertRedirect();
        $this->assertAuthenticatedAs($customer, 'customer');

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.password.edit'));
        $this->get(route('portal.password.edit'))->assertOk()->assertSee('initial password');
    }

    public function test_password_change_rules_and_unlock(): void
    {
        $customer = $this->customer();
        $this->loginAs($customer);

        $this->put(route('portal.password.update'), ['current_password' => 'wrong', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('portal.password.update'), ['current_password' => $customer->customer_code, 'password' => 'kurang1', 'password_confirmation' => 'kurang1'])
            ->assertSessionHasErrors('password'); // shorter than 8
        $this->put(route('portal.password.update'), ['current_password' => $customer->customer_code, 'password' => 'passwordbaru1', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');

        $this->put(route('portal.password.update'), ['current_password' => $customer->customer_code, 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertTrue(Hash::check('passwordbaru1', $customer->fresh()->password));
        $this->assertFalse($customer->fresh()->must_change_password);
        $this->get(route('portal.dashboard'))->assertOk();
    }

    public function test_wrong_credentials_and_ineligible_customers_cannot_log_in(): void
    {
        $customer = $this->customer();
        $inactive = Customer::factory()->withPortalAccess()->create(['is_active' => false]);
        $pending = Customer::factory()->pendingApproval()->create();

        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'salah'])->assertSessionHasErrors('customer_code');
        $this->post(route('portal.login.store'), ['customer_code' => $inactive->customer_code, 'password' => $inactive->customer_code])->assertSessionHasErrors('customer_code');
        $this->post(route('portal.login.store'), ['customer_code' => 'tidak-ada', 'password' => 'x'])->assertSessionHasErrors('customer_code');
        $pending->forceFill(['customer_code' => '269999', 'password' => '269999'])->save();
        $this->post(route('portal.login.store'), ['customer_code' => '269999', 'password' => '269999'])->assertSessionHasErrors('customer_code');

        $this->assertGuest('customer');
    }

    public function test_repeated_wrong_passwords_lock_the_login_for_that_id(): void
    {
        $customer = $this->customer();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'salah'])->assertSessionHasErrors('customer_code');
        }

        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => $customer->customer_code])
            ->assertSessionHasErrors('customer_code');
        $this->assertGuest('customer');
    }

    public function test_guests_are_sent_to_the_portal_login_and_portals_are_separate_from_the_erp(): void
    {
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));

        $this->loginAs($this->customer('rahasia123'));
        $this->get('/erp/dashboard')->assertRedirect(route('login'));
        $this->post(route('portal.logout'));

        $this->actingAs($this->financeUser(), 'web');
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    }

    public function test_dashboard_shows_schedule_place_progress_and_links(): void
    {
        $customer = $this->customer('rahasia123');
        $session = TrainingSession::factory()->create([
            'customer_id' => $customer->id, 'location' => 'Kantor ALF', 'materials_url' => 'https://drive.test/materi', 'certificate_url' => null,
        ]);
        TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id, 'meeting_date' => '2026-10-05', 'location' => 'Ruang A', 'topic' => 'Pengenalan', 'is_completed' => true,
        ]);
        TrainingSessionMeeting::factory()->create([
            'training_session_id' => $session->id, 'meeting_date' => '2026-10-12', 'location' => null, 'topic' => 'Lanjutan', 'is_completed' => false,
        ]);
        $other = TrainingSession::factory()->create();
        $this->loginAs($customer);

        $this->get(route('portal.dashboard'))->assertOk()
            ->assertSee($session->program->name)
            ->assertSee('Ruang A')
            ->assertSee('Kantor ALF')
            ->assertSee('1 of 2 meetings done')
            ->assertSee('Done')->assertSee('Upcoming')
            ->assertSee('https://drive.test/materi')
            ->assertDontSee('>Certificate<', false)
            ->assertDontSee($other->program->name);

        $session->update(['certificate_url' => 'https://drive.test/sertifikat']);
        $this->get(route('portal.dashboard'))->assertSee('https://drive.test/sertifikat');
    }

    public function test_dashboard_links_to_the_real_certificate_once_the_session_is_marked_done(): void
    {
        $customer = $this->customer('rahasia123');
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'status' => 'completed']);
        CertificateIssuer::issueFor($session);
        $certificate = Certificate::where('training_session_id', $session->id)->firstOrFail();
        $this->loginAs($customer);

        // The auto-issued certificate is used, not a manually pasted link the staff never filled in.
        $this->get(route('portal.dashboard'))->assertOk()
            ->assertSee(route('portal.certificates.show', $certificate), false)
            ->assertDontSee('drive.test');

        // Once staff also pastes a manual link, the real certificate still takes priority.
        $session->update(['certificate_url' => 'https://drive.test/sertifikat']);
        $this->get(route('portal.dashboard'))
            ->assertSee(route('portal.certificates.show', $certificate), false)
            ->assertDontSee('drive.test');
    }

    public function test_customer_uploads_a_project_file_or_link(): void
    {
        $customer = $this->customer('rahasia123');
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id]);
        $this->loginAs($customer);

        $this->post(route('portal.projects.store'), [
            'training_session_id' => $session->id, 'title' => 'Dashboard Penjualan', 'description' => 'Hasil belajar',
            'file' => UploadedFile::fake()->create('dashboard.zip', 200, 'application/zip'),
        ])->assertRedirect(route('portal.dashboard'));

        $project = CustomerProject::firstOrFail();
        $this->assertSame($customer->id, $project->customer_id);
        $this->assertSame('dashboard.zip', $project->file_name);
        Storage::disk('local')->assertExists($project->file_path);

        $this->post(route('portal.projects.store'), [
            'training_session_id' => $session->id, 'title' => 'Repo GitHub', 'external_url' => 'https://github.com/x/y',
        ])->assertRedirect(route('portal.dashboard'));
        $this->assertSame(2, CustomerProject::count());

        $this->get(route('portal.dashboard'))->assertSee('Dashboard Penjualan')->assertSee('Repo GitHub');
    }

    public function test_project_upload_validation(): void
    {
        $customer = $this->customer('rahasia123');
        $mine = TrainingSession::factory()->create(['customer_id' => $customer->id]);
        $theirs = TrainingSession::factory()->create();
        $this->loginAs($customer);

        $this->post(route('portal.projects.store'), ['training_session_id' => $mine->id, 'title' => 'Kosong'])->assertSessionHasErrors('file');
        $this->post(route('portal.projects.store'), ['training_session_id' => $theirs->id, 'title' => 'Bukan Punya Saya', 'external_url' => 'https://a.test'])
            ->assertSessionHasErrors('training_session_id');
        $this->post(route('portal.projects.store'), [
            'training_session_id' => $mine->id, 'title' => 'Berbahaya', 'file' => UploadedFile::fake()->create('shell.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('file');
        $this->post(route('portal.projects.store'), [
            'training_session_id' => $mine->id, 'title' => 'Besar', 'file' => UploadedFile::fake()->create('big.zip', 11 * 1024, 'application/zip'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, CustomerProject::count());
    }

    public function test_only_the_owner_can_download_a_project_file(): void
    {
        $owner = $this->customer('rahasia123');
        $stranger = $this->customer('lainnya123');
        Storage::disk('local')->put('customer-projects/1/a.zip', 'isi');
        $project = CustomerProject::factory()->create(['customer_id' => $owner->id, 'file_path' => 'customer-projects/1/a.zip', 'file_name' => 'a.zip']);

        $this->loginAs($stranger);
        $this->get(route('portal.projects.download', $project))->assertForbidden();
        $this->post(route('portal.logout'));

        $this->loginAs($owner);
        $this->get(route('portal.projects.download', $project))->assertOk()->assertDownload('a.zip');
    }

    public function test_company_sees_uploads_downloads_them_and_marks_the_portfolio(): void
    {
        $customer = Customer::factory()->create();
        Storage::disk('local')->put('customer-projects/1/a.zip', 'isi');
        $project = CustomerProject::factory()->create(['customer_id' => $customer->id, 'title' => 'Proyek Akhir', 'file_path' => 'customer-projects/1/a.zip', 'file_name' => 'a.zip']);
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.show', $customer))->assertSee('Proyek Akhir')->assertSee('Mark as added');
        $this->actingAs($finance)->get(route('sales.projects.download', $project))->assertOk()->assertDownload('a.zip');

        $this->actingAs($finance)->patch(route('sales.projects.portfolio', $project))->assertRedirect(route('sales.show', $customer));
        $this->assertTrue($project->fresh()->in_portfolio);
        $this->actingAs($finance)->get(route('sales.show', $customer))->assertSee('In portfolio');
    }

    public function test_deleting_a_project_removes_its_file(): void
    {
        Storage::disk('local')->put('customer-projects/1/a.zip', 'isi');
        $project = CustomerProject::factory()->create(['file_path' => 'customer-projects/1/a.zip']);

        $project->delete();

        Storage::disk('local')->assertMissing('customer-projects/1/a.zip');
    }

    public function test_view_only_user_cannot_toggle_the_portfolio_mark(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'sales.view']);
        $project = CustomerProject::factory()->create();

        $this->actingAs($viewer)->patch(route('sales.projects.portfolio', $project))->assertForbidden();
    }

    public function test_logout_ends_the_portal_session(): void
    {
        $this->loginAs($this->customer('rahasia123'));

        $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));
        $this->assertGuest('customer');
    }
}
