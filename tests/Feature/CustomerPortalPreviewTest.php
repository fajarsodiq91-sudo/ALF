<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerProject;
use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerPortalPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');
    }

    private function staff(string $role = 'Finance'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** Opens the preview as staff and returns the customer the portal now belongs to. */
    private function preview(Customer $customer): void
    {
        $this->actingAs($this->staff(), 'web')->get(route('customer-portal.open', $customer))->assertRedirect(route('portal.dashboard'));
    }

    public function test_menu_page_lists_only_approved_customers_with_their_login_state(): void
    {
        Customer::factory()->create(['name' => 'PT Manual']); // added by staff: no password
        Customer::factory()->withPortalAccess()->create(['name' => 'PT Baru Login']); // initial password
        Customer::factory()->withPortalAccess('rahasia123')->create(['name' => 'PT Aktif']);
        Customer::factory()->pendingApproval()->create(['name' => 'PT Menunggu']);
        Customer::factory()->awaitingCustomer()->create();

        $this->actingAs($this->staff(), 'web')->get(route('customer-portal.index'))->assertOk()
            ->assertSee('Customer Portal')->assertSee(route('portal.login'), false)->assertSee('read-only')
            ->assertSee('PT Manual')->assertSee('No login (added by staff)')
            ->assertSee('PT Baru Login')->assertSee('Initial password not changed yet')
            ->assertSee('PT Aktif')->assertSee('Active')
            ->assertDontSee('PT Menunggu');
    }

    public function test_the_menu_is_in_the_sidebar_only_for_people_who_can_view_sales(): void
    {
        $this->actingAs($this->staff(), 'web')->get(route('dashboard'))->assertSee('Customer Portal');

        $staff = $this->staff('Staff');
        $this->actingAs($staff, 'web')->get(route('dashboard'))->assertDontSee('Customer Portal');
        $this->actingAs($staff, 'web')->get(route('customer-portal.index'))->assertForbidden();
    }

    public function test_opening_a_customer_shows_their_portal_with_a_preview_banner(): void
    {
        $customer = Customer::factory()->withPortalAccess()->create(['name' => 'PT Dilihat']); // initial password: a real login would be forced to change it
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id, 'materials_url' => 'https://drive.test/materi']);
        TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'topic' => 'Topik Rahasia']);

        $this->preview($customer);

        $this->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Preview mode')->assertSee('This view is read-only')
            ->assertSee('PT Dilihat')->assertSee($session->program->name)->assertSee('Topik Rahasia')->assertSee('https://drive.test/materi')
            ->assertSee('Exit preview')->assertDontSee('Log out')
            ->assertSee('Uploading is disabled in preview mode')
            ->assertDontSee('Upload project');
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_preview_works_for_customers_without_a_password(): void
    {
        $customer = Customer::factory()->create(['name' => 'PT Tanpa Login']);

        $this->preview($customer);

        $this->get(route('portal.dashboard'))->assertOk()->assertSee('PT Tanpa Login')->assertSee('Preview mode');
    }

    public function test_only_approved_customers_can_be_previewed(): void
    {
        $staff = $this->staff();

        foreach ([Customer::factory()->pendingApproval()->create(), Customer::factory()->awaitingCustomer()->create()] as $customer) {
            $this->actingAs($staff, 'web')->get(route('customer-portal.open', $customer))->assertRedirect(route('customer-portal.index'))->assertSessionHas('error');
            $this->assertGuest('customer');
        }
    }

    public function test_people_without_sales_access_cannot_open_a_preview(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->staff('Staff'), 'web')->get(route('customer-portal.open', $customer))->assertForbidden();
        $this->assertGuest('customer');
    }

    public function test_the_preview_cannot_change_anything(): void
    {
        $customer = Customer::factory()->withPortalAccess()->create();
        $session = TrainingSession::factory()->create(['customer_id' => $customer->id]);
        $originalPassword = $customer->fresh()->password;
        $this->preview($customer);

        $this->post(route('portal.projects.store'), [
            'training_session_id' => $session->id, 'title' => 'Diam-diam', 'external_url' => 'https://a.test',
        ])->assertRedirect(route('portal.dashboard'))->assertSessionHas('error');
        $this->assertSame(0, CustomerProject::count());

        $this->put(route('portal.password.update'), [
            'current_password' => $customer->customer_code, 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect(route('portal.dashboard'))->assertSessionHas('error');
        $this->assertSame($originalPassword, $customer->fresh()->password);
        $this->assertTrue($customer->fresh()->must_change_password);

        $this->get(route('portal.password.edit'))->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.dashboard'))->assertSee('Passwords cannot be changed in preview mode');
    }

    public function test_files_can_still_be_downloaded_in_the_preview(): void
    {
        $customer = Customer::factory()->create();
        Storage::disk('local')->put('customer-projects/1/a.zip', 'isi');
        $project = CustomerProject::factory()->create(['customer_id' => $customer->id, 'file_path' => 'customer-projects/1/a.zip', 'file_name' => 'a.zip']);
        $this->preview($customer);

        $this->get(route('portal.projects.download', $project))->assertOk()->assertDownload('a.zip');
    }

    public function test_exit_preview_returns_to_the_erp_and_ends_the_customer_session(): void
    {
        $customer = Customer::factory()->create();
        $this->preview($customer);

        $this->post(route('portal.logout'))->assertRedirect(route('customer-portal.index'));

        $this->assertGuest('customer');
        $this->assertFalse(session()->has('portal_preview'));
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    }

    public function test_a_real_customer_login_afterwards_is_not_read_only(): void
    {
        $preview = Customer::factory()->create();
        $real = Customer::factory()->withPortalAccess('rahasia123')->create();
        $session = TrainingSession::factory()->create(['customer_id' => $real->id]);
        $this->preview($preview);
        $this->post(route('portal.logout'));

        $this->post(route('portal.login.store'), ['customer_code' => $real->customer_code, 'password' => 'rahasia123'])->assertRedirect();
        $this->post(route('portal.projects.store'), [
            'training_session_id' => $session->id, 'title' => 'Sungguhan', 'external_url' => 'https://a.test',
        ])->assertRedirect(route('portal.dashboard'))->assertSessionHas('status');

        $this->assertSame(1, CustomerProject::count());
        $this->get(route('portal.dashboard'))->assertDontSee('Preview mode')->assertSee('Log out');
    }

    public function test_a_real_login_while_a_preview_flag_is_left_over_clears_it(): void
    {
        $preview = Customer::factory()->create();
        $real = Customer::factory()->withPortalAccess('rahasia123')->create();
        $this->preview($preview);

        // The customer signs in from the same browser without leaving the preview first.
        $this->post(route('portal.logout'));
        $this->app['auth']->guard('customer')->logout();
        $this->post(route('portal.login.store'), ['customer_code' => $real->customer_code, 'password' => 'rahasia123'])->assertRedirect();

        $this->assertFalse(session()->has('portal_preview'));
    }

    public function test_normal_customers_are_still_forced_to_change_their_initial_password(): void
    {
        $customer = Customer::factory()->withPortalAccess()->create();

        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => $customer->customer_code]);

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.password.edit'));
        $this->assertTrue(Hash::check($customer->customer_code, $customer->fresh()->password));
    }
}
