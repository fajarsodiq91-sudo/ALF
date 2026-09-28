<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationReceived;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('public');
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

    private function photo(string $name = 'photo.png'): UploadedFile
    {
        // 1x1 transparent PNG (the server has no GD, so UploadedFile::image() is unavailable).
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));
    }

    private function invite(string $type = 'company'): Customer
    {
        $this->actingAs($this->financeUser())->post(route('sales.invite.store'), ['customer_type' => $type]);

        return Customer::firstOrFail();
    }

    public function test_admin_only_picks_type_and_gets_a_qr_page(): void
    {
        $finance = $this->financeUser();

        $response = $this->actingAs($finance)->post(route('sales.invite.store'), ['customer_type' => 'government']);
        $customer = Customer::firstOrFail();

        $response->assertRedirect(route('sales.invite.show', $customer));
        $this->assertTrue($customer->isAwaitingCustomer());
        $this->assertNull($customer->name);
        $this->assertNull($customer->customer_code);
        $this->assertSame('government', $customer->customer_type);

        $page = $this->actingAs($finance)->get(route('sales.invite.show', $customer));
        $page->assertOk()->assertSee('<svg', false)->assertSee(route('customer-registration.show', $customer->registration_token), false);
    }

    public function test_invite_requires_a_valid_type_and_permission(): void
    {
        $this->actingAs($this->financeUser())->post(route('sales.invite.store'), ['customer_type' => 'bogus'])
            ->assertSessionHasErrors('customer_type');

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'sales.view']);
        $this->actingAs($viewer)->post(route('sales.invite.store'), ['customer_type' => 'company'])->assertForbidden();
        $this->assertSame(0, Customer::count());
    }

    public function test_customer_submits_publicly_and_then_waits_for_approval(): void
    {
        Mail::fake();
        $customer = $this->invite();
        $token = $customer->registration_token;

        $this->get(route('customer-registration.show', $token))->assertOk()->assertSee('Customer Registration');

        $this->post(route('customer-registration.store', $token), [
            'name' => 'PT Pelanggan Baru', 'email' => 'a@b.test', 'phone' => '0812', 'city' => 'Bandung', 'photo' => $this->photo(),
        ])->assertRedirect(route('customer-registration.done'));

        $customer->refresh();
        $this->assertSame('PT Pelanggan Baru', $customer->name);
        $this->assertSame('company', $customer->customer_type);
        $this->assertTrue($customer->isPendingApproval());
        $this->assertNull($customer->customer_code);
        $this->assertNull($customer->password);
        $this->assertNull($customer->registration_token);
        Storage::disk('public')->assertExists($customer->photo_path);

        $this->get(route('customer-registration.done'))->assertSee('waiting for approval')->assertSee('a@b.test');
        Mail::assertSent(CustomerRegistrationReceived::class, fn ($mail) => $mail->hasTo('a@b.test'));
    }

    public function test_email_is_required_on_the_public_form(): void
    {
        $token = $this->invite()->registration_token;

        $this->post(route('customer-registration.store', $token), ['name' => 'Tanpa Email'])->assertSessionHasErrors('email');
    }

    public function test_a_failing_mail_server_does_not_lose_the_registration(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));
        $customer = $this->invite();

        $this->post(route('customer-registration.store', $customer->registration_token), ['name' => 'Tetap Masuk', 'email' => 'x@y.test'])
            ->assertRedirect(route('customer-registration.done'));

        $this->assertTrue($customer->fresh()->isPendingApproval());
    }

    public function test_link_works_only_once(): void
    {
        $customer = $this->invite();
        $token = $customer->registration_token;

        $this->post(route('customer-registration.store', $token), ['name' => 'Sekali', 'email' => 's@b.test'])->assertRedirect();

        $this->get(route('customer-registration.show', $token))->assertStatus(410);
        $this->post(route('customer-registration.store', $token), ['name' => 'Dua Kali', 'email' => 'd@b.test'])->assertStatus(410);
        $this->assertSame('Sekali', $customer->fresh()->name);
    }

    public function test_expired_and_unknown_links_are_rejected(): void
    {
        $customer = $this->invite();
        $token = $customer->registration_token;

        Carbon::setTestNow('2026-09-23 10:00:00');
        $this->get(route('customer-registration.show', $token))->assertStatus(410);
        $this->get(route('customer-registration.show', 'not-a-real-token'))->assertStatus(410);
    }

    public function test_regenerating_invalidates_the_old_link(): void
    {
        $customer = $this->invite();
        $old = $customer->registration_token;

        $this->actingAs($this->financeUser())->post(route('sales.invite.regenerate', $customer))->assertRedirect();

        $new = $customer->fresh()->registration_token;
        $this->assertNotSame($old, $new);
        $this->get(route('customer-registration.show', $old))->assertStatus(410);
        $this->get(route('customer-registration.show', $new))->assertOk();
    }

    public function test_public_form_validates_input_and_rejects_non_images(): void
    {
        $token = $this->invite()->registration_token;

        $this->post(route('customer-registration.store', $token), [
            'name' => '', 'email' => 'nope', 'photo' => UploadedFile::fake()->create('evil.php', 10, 'text/plain'),
        ])->assertSessionHasErrors(['name', 'email', 'photo']);

        $this->post(route('customer-registration.store', $token), [
            'name' => 'Terlalu Besar', 'photo' => UploadedFile::fake()->createWithContent('big.png', str_repeat('x', 3 * 1024 * 1024)),
        ])->assertSessionHasErrors('photo');
    }

    public function test_pending_customers_are_hidden_from_other_modules_and_use_no_id(): void
    {
        $this->invite();
        $done = Customer::factory()->create(['name' => 'PT Selesai']);
        $finance = $this->financeUser();

        $this->assertSame('260901', $done->customer_code); // pending invite did not consume a number
        $this->actingAs($finance)->get(route('projects.create'))->assertSee('PT Selesai');
        $this->assertCount(1, $this->actingAs($finance)->get(route('projects.create'))->viewData('customers'));
        $this->assertCount(1, $this->actingAs($finance)->get(route('training.create'))->viewData('customers'));
    }

    public function test_index_shows_pending_and_filters_them(): void
    {
        $this->invite();
        Customer::factory()->create(['name' => 'PT Lengkap']);
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.index'))->assertOk()->assertSee('Awaiting customer')->assertSee('PT Lengkap');
        $this->actingAs($finance)->get(route('sales.index', ['status' => 'awaiting']))
            ->assertSee('Waiting for customer to fill in')->assertDontSee('PT Lengkap');
    }

    public function test_admin_can_complete_a_pending_customer_manually(): void
    {
        $customer = $this->invite();
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.edit', $customer))->assertOk()->assertSee('Save &amp; Complete', false);
        $this->actingAs($finance)->put(route('sales.update', $customer), [
            'name' => 'Diisi Admin', 'customer_type' => 'company', 'is_active' => '1',
        ])->assertRedirect(route('sales.index'));

        $customer->refresh();
        $this->assertSame('260901', $customer->customer_code);
        $this->assertNull($customer->registration_token);
    }

    public function test_admin_can_upload_replace_and_remove_photo(): void
    {
        $finance = $this->financeUser();

        $this->actingAs($finance)->post(route('sales.store'), [
            'name' => 'PT Foto', 'customer_type' => 'company', 'is_active' => '1', 'photo' => $this->photo(),
        ])->assertRedirect(route('sales.index'));
        $customer = Customer::firstOrFail();
        $first = $customer->photo_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($finance)->put(route('sales.update', $customer), [
            'name' => 'PT Foto', 'customer_type' => 'company', 'is_active' => '1', 'photo' => $this->photo('new.png'),
        ]);
        $second = $customer->fresh()->photo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($finance)->put(route('sales.update', $customer), [
            'name' => 'PT Foto', 'customer_type' => 'company', 'is_active' => '1', 'remove_photo' => '1',
        ]);
        $this->assertNull($customer->fresh()->photo_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_deleting_a_customer_removes_the_photo_file(): void
    {
        $finance = $this->financeUser();
        $this->actingAs($finance)->post(route('sales.store'), [
            'name' => 'PT Hapus', 'customer_type' => 'company', 'is_active' => '1', 'photo' => $this->photo(),
        ]);
        $customer = Customer::firstOrFail();
        $path = $customer->photo_path;

        $this->actingAs($finance)->delete(route('sales.destroy', $customer));

        Storage::disk('public')->assertMissing($path);
    }
}
