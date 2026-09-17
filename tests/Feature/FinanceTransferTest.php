<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function financeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Finance');

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Viewer');

        return $user;
    }

    public function test_finance_role_can_view_transfer_list(): void
    {
        Transfer::factory()->count(3)->create();
        $user = $this->financeUser();

        $response = $this->actingAs($user)->get(route('finance.transfers'));

        $response->assertOk();
        $response->assertSee('Move funds between accounts');
    }

    public function test_finance_role_can_create_transfer(): void
    {
        $fromAccount = Account::factory()->create();
        $toAccount = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.transfers.store'), [
            'transfer_date' => '2025-09-17',
            'amount' => 5_000_000,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'description' => 'Monthly transfer to operational account',
        ]);

        $response->assertRedirect(route('finance.transfers'));
        $this->assertDatabaseHas('transfers', [
            'amount' => 5_000_000,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);
    }

    public function test_transfer_creation_requires_valid_data(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.transfers.store'), [
            'transfer_date' => 'not-a-date',
            'amount' => -5,
            'from_account_id' => 999,
            'to_account_id' => 999,
        ]);

        $response->assertSessionHasErrors(['transfer_date', 'amount', 'from_account_id', 'to_account_id']);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_cannot_be_to_same_account(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.transfers.store'), [
            'transfer_date' => '2025-09-17',
            'amount' => 1_000_000,
            'from_account_id' => $account->id,
            'to_account_id' => $account->id,
        ]);

        $response->assertSessionHasErrors(['to_account_id']);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_viewer_role_cannot_create_transfer(): void
    {
        $fromAccount = Account::factory()->create();
        $toAccount = Account::factory()->create();

        $response = $this->actingAs($this->viewerUser())->post(route('finance.transfers.store'), [
            'transfer_date' => '2025-09-17',
            'amount' => 1_000_000,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_viewer_role_cannot_access_create_form(): void
    {
        $response = $this->actingAs($this->viewerUser())->get(route('finance.transfers.create'));

        $response->assertForbidden();
    }

    public function test_finance_role_can_update_transfer(): void
    {
        $transfer = Transfer::factory()->create(['amount' => 1_000_000]);
        $newAccount = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->put(route('finance.transfers.update', $transfer), [
            'transfer_date' => $transfer->transfer_date->format('Y-m-d'),
            'amount' => 2_000_000,
            'from_account_id' => $transfer->from_account_id,
            'to_account_id' => $newAccount->id,
            'description' => 'Updated transfer',
        ]);

        $response->assertRedirect(route('finance.transfers'));
        $this->assertDatabaseHas('transfers', [
            'id' => $transfer->id,
            'amount' => 2_000_000,
            'description' => 'Updated transfer',
        ]);
    }

    public function test_finance_role_can_delete_transfer(): void
    {
        $transfer = Transfer::factory()->create();

        $response = $this->actingAs($this->financeUser())->delete(route('finance.transfers.destroy', $transfer));

        $response->assertRedirect(route('finance.transfers'));
        $this->assertDatabaseMissing('transfers', ['id' => $transfer->id]);
    }

    public function test_transfer_is_auto_numbered(): void
    {
        $fromAccount = Account::factory()->create();
        $toAccount = Account::factory()->create();

        $response = $this->actingAs($this->financeUser())->post(route('finance.transfers.store'), [
            'transfer_date' => '2025-09-17',
            'amount' => 1_000_000,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);

        $response->assertRedirect();
        $transfer = Transfer::latest()->first();
        $this->assertNotNull($transfer);
        $this->assertStringStartsWith('TRF-', $transfer->transfer_number);
    }

    public function test_created_by_is_set_automatically(): void
    {
        $user = $this->financeUser();
        $fromAccount = Account::factory()->create();
        $toAccount = Account::factory()->create();

        $this->actingAs($user)->post(route('finance.transfers.store'), [
            'transfer_date' => '2025-09-17',
            'amount' => 1_000_000,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);

        $transfer = Transfer::latest()->first();
        $this->assertEquals($user->id, $transfer->created_by);
    }
}
