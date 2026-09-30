<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AgreementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    private function registrationUrl(): string
    {
        $this->actingAs($this->admin())->post(route('sales.invite.store'), ['customer_type' => 'company']);

        return route('customer-registration.show', Customer::firstOrFail()->registration_token);
    }

    public function test_form_hides_the_link_and_page_is_missing_until_an_agreement_is_written(): void
    {
        $this->get($this->registrationUrl())->assertOk()->assertDontSee('Read the full agreement');
        $this->get(route('customer-registration.agreement'))->assertNotFound();
    }

    public function test_admin_writes_the_agreement_and_customers_read_it_from_the_form_link(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('masterdata.agreement.update'), ['agreement_content' => "1. Payment\nPay on time <b>please</b>"])
            ->assertRedirect(route('masterdata.agreement.edit'));

        $this->actingAs($admin)->get(route('masterdata.agreement.edit'))->assertOk()->assertSee('1. Payment');
        $this->get($this->registrationUrl())->assertOk()
            ->assertSee('Read the full agreement')->assertSee(route('customer-registration.agreement'), false);

        auth()->logout();
        $this->get(route('customer-registration.agreement'))->assertOk()
            ->assertSee('1. Payment')->assertSee('&lt;b&gt;please&lt;/b&gt;', false);
    }

    public function test_agreement_can_be_cleared_again(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('masterdata.agreement.update'), ['agreement_content' => 'Terms']);
        $this->actingAs($admin)->put(route('masterdata.agreement.update'), ['agreement_content' => null])->assertSessionHasNoErrors();

        $this->get($this->registrationUrl())->assertDontSee('Read the full agreement');
        $this->get(route('customer-registration.agreement'))->assertNotFound();
    }

    public function test_only_master_data_managers_can_change_the_agreement(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp']);

        $this->actingAs($viewer)->put(route('masterdata.agreement.update'), ['agreement_content' => 'x'])->assertForbidden();
        $this->actingAs($viewer)->get(route('masterdata.agreement.edit'))->assertForbidden();
    }
}
