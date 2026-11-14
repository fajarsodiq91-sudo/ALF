<?php

namespace Tests\Feature;

use App\Mail\CustomerRegistrationApproved;
use App\Mail\CustomerRegistrationReceived;
use App\Mail\ServiceOrderMail;
use App\Mail\StaffRegistrationSubmitted;
use App\Models\Account;
use App\Models\Customer;
use App\Models\IncomeTransaction;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderFile;
use App\Models\ServicePackage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('local');
        Mail::fake();
        Carbon::setTestNow('2026-11-14 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function staff(string $role = 'Finance'): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->withPortalAccess('secret-pass')->create(['email' => 'budi@example.com', 'customer_type' => 'individual', ...$attributes]);
    }

    /** @return array<string, mixed> */
    private function brief(array $overrides = []): array
    {
        return [
            'title' => 'Sales dashboard',
            'purpose' => 'Track monthly sales per branch.',
            'kpis' => 'Revenue, margin',
            'data_sources' => 'Excel export from POS',
            'needs_refresh' => '1',
            ...$overrides,
        ];
    }

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return [
            'items' => [['label' => 'Dashboard', 'amount' => 1500000], ['label' => 'Extra page', 'amount' => 500000]],
            'dp_percentage' => 50,
            'estimated_days' => 7,
            'included_revisions' => 1,
            'quote_valid_until' => '2026-12-01',
            'quote_notes' => 'Includes one data source.',
            ...$overrides,
        ];
    }

    private function payPayload(array $overrides = []): array
    {
        return ['payment_method' => 'Cash', 'paid_date' => '2026-11-14', ...$overrides];
    }

    // ---- Customers order from the portal ----

    public function test_an_existing_customer_orders_from_the_portal_and_staff_are_notified(): void
    {
        $customer = $this->customer();
        $package = ServicePackage::factory()->create(['addons' => [['label' => 'Extra page', 'price' => 300000], ['label' => 'Automation', 'price' => 500000]]]);
        $this->staff();

        $this->actingAs($customer, 'customer')->get(route('portal.orders.create'))->assertOk()->assertSee($package->name);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.store'), $this->brief([
            'service_package_id' => $package->id,
            'addons' => ['Extra page', 'Not a real addon'],
            'files' => [UploadedFile::fake()->create('sales.xlsx', 50)],
        ]))->assertRedirect();

        $order = ServiceOrder::firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(ServiceOrder::BRIEF, $order->status);
        $this->assertTrue($order->needs_refresh);
        $this->assertSame([['label' => 'Extra page', 'price' => 300000]], $order->addons, 'only add-ons of the chosen package are kept, priced as listed');
        $this->assertSame(1, $order->files()->where('kind', 'brief')->count());
        Storage::disk('local')->assertExists($order->files->first()->path);
        Mail::assertSent(ServiceOrderMail::class, fn ($mail) => $mail->hasTo(User::role('Finance')->first()->email) && str_contains($mail->mailSubject, 'New service request'));

        $this->actingAs($customer, 'customer')->get(route('portal.orders.index'))->assertOk()->assertSee('Sales dashboard');
        $this->actingAs($customer, 'customer')->get(route('portal.dashboard'))->assertOk()->assertSee(route('portal.orders.index'), false);
    }

    public function test_the_brief_needs_a_title_purpose_and_data_source(): void
    {
        $this->actingAs($this->customer(), 'customer')->post(route('portal.orders.store'), ['title' => ''])
            ->assertSessionHasErrors(['title', 'purpose', 'data_sources']);

        $this->assertSame(0, ServiceOrder::count());
    }

    public function test_a_participant_cannot_order(): void
    {
        $company = $this->customer(['customer_type' => 'company']);
        $participant = $this->customer(['company_customer_id' => $company->id]);

        $this->actingAs($participant, 'customer')->get(route('portal.orders.create'))->assertRedirect(route('portal.orders.index'));
        $this->actingAs($participant, 'customer')->post(route('portal.orders.store'), $this->brief())->assertForbidden();
    }

    public function test_a_customer_only_sees_their_own_orders(): void
    {
        $order = ServiceOrder::factory()->create();
        $other = $this->customer(['email' => 'other@example.com']);

        $this->actingAs($other, 'customer')->get(route('portal.orders.show', $order))->assertNotFound();
        $this->actingAs($other, 'customer')->post(route('portal.orders.accept', $order))->assertNotFound();
    }

    // ---- New customers through a custom-work QR invitation ----

    public function test_a_new_customer_registers_for_custom_work_through_the_qr_link_and_is_approved_without_programs(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('sales.invite.store'), ['customer_type' => 'individual', 'registration_track' => 'service'])->assertRedirect();
        $customer = Customer::firstOrFail();
        $this->assertTrue($customer->isServiceTrack());
        auth()->logout();

        $this->get(route('customer-registration.show', $customer->registration_token))->assertOk()->assertSee('Request custom work');

        $this->post(route('customer-registration.store', $customer->registration_token), [
            'name' => 'Siti', 'email' => 'siti@example.com', 'phone' => '0811111111',
            ...$this->brief(),
        ])->assertRedirect();

        $customer->refresh();
        $this->assertTrue($customer->isPendingApproval());
        $order = $customer->serviceOrders()->firstOrFail();
        $this->assertSame('Sales dashboard', $order->title);

        $this->actingAs($staff)->get(route('sales.review', $customer))->assertOk()->assertSee('Custom work request: Sales dashboard');
        $this->actingAs($staff)->post(route('sales.approve', $customer))->assertRedirect(route('sales.show', $customer));

        $customer->refresh();
        $this->assertNotNull($customer->customer_code);
        $this->assertSame(0, $customer->sessions()->count());
        $this->assertSame(ServiceOrder::BRIEF, $order->fresh()->status);
    }

    public function test_a_learning_invitation_still_needs_programs_to_approve(): void
    {
        $customer = Customer::factory()->pendingApproval()->create(['customer_type' => 'individual']);

        $this->actingAs($this->staff())->post(route('sales.approve', $customer))->assertSessionHasErrors('programs');
    }

    // ---- The whole life of an order ----

    public function test_an_order_goes_from_brief_to_completed_with_payments_in_finance(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        $order = ServiceOrder::factory()->create(['customer_id' => $customer->id]);

        // Quote
        $this->actingAs($staff)->post(route('services.orders.quote', $order), $this->quote())->assertRedirect();
        $order->refresh();
        $this->assertSame(ServiceOrder::QUOTED, $order->status);
        $this->assertEquals(2000000, $order->quote_total);
        Mail::assertSent(ServiceOrderMail::class, fn ($mail) => $mail->hasTo('budi@example.com') && str_contains($mail->mailSubject, 'Quote for'));
        $this->actingAs($customer, 'customer')->get(route('portal.orders.show', $order))->assertSee('Accept quote')->assertSee('Rp 2.000.000');

        // Accept: DP and final invoices, DP emailed at once
        $this->actingAs($customer, 'customer')->post(route('portal.orders.accept', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame(ServiceOrder::ACCEPTED, $order->status);
        [$dp, $final] = $order->payments->all();
        $this->assertEquals(1000000, $dp->amount);
        $this->assertEquals(1000000, $final->amount);
        $this->assertNotNull($dp->invoice_sent_at);
        $this->assertNull($final->invoice_sent_at, 'the final invoice waits for delivery');

        // Cannot deliver before the down payment
        $this->actingAs($staff)->post(route('services.orders.deliver', $order))->assertSessionHas('error');

        // DP recorded -> income in Finance, work starts
        $this->actingAs($staff)->post(route('services.payments.pay', $dp), $this->payPayload())->assertRedirect();
        $order->refresh();
        $this->assertSame(ServiceOrder::IN_PROGRESS, $order->status);
        $this->assertTrue($dp->fresh()->isPaid());
        $this->assertSame(1, IncomeTransaction::count());
        $this->assertEquals(1000000, IncomeTransaction::first()->amount);

        // Deliver needs a file
        $this->actingAs($staff)->post(route('services.orders.deliver', $order))->assertSessionHas('error');
        $this->actingAs($staff)->post(route('services.orders.files.store', $order), ['kind' => 'draft', 'files' => [UploadedFile::fake()->create('draft.xlsx', 20)]])->assertRedirect();
        $this->actingAs($staff)->post(route('services.orders.files.store', $order), ['kind' => 'final', 'files' => [UploadedFile::fake()->create('final.xlsx', 20)]])->assertRedirect();
        $this->actingAs($staff)->post(route('services.orders.deliver', $order))->assertSessionHas('status');

        $order->refresh();
        $this->assertSame(ServiceOrder::DELIVERED, $order->status);
        $this->assertNotNull($final->fresh()->invoice_sent_at, 'delivery sends the final invoice');

        // Final files stay locked while unpaid; drafts do not
        $draft = $order->files()->where('kind', 'draft')->first();
        $finalFile = $order->files()->where('kind', 'final')->first();
        $this->actingAs($customer, 'customer')->get(route('portal.orders.files.download', $draft))->assertOk();
        $this->actingAs($customer, 'customer')->get(route('portal.orders.files.download', $finalFile))->assertRedirect(route('portal.orders.show', $order));

        // Final payment -> completed, final files released
        $this->actingAs($staff)->post(route('services.payments.pay', $final), $this->payPayload())->assertRedirect();
        $this->assertSame(ServiceOrder::COMPLETED, $order->fresh()->status);
        $this->actingAs($customer, 'customer')->get(route('portal.orders.files.download', $finalFile))->assertOk();
        $this->assertSame(2, IncomeTransaction::count());

        // Cancelling the final payment takes the order back to delivered and locks the files again
        $this->actingAs($staff)->post(route('services.payments.cancel', $final))->assertRedirect();
        $this->assertSame(ServiceOrder::DELIVERED, $order->fresh()->status);
        $this->assertSame(1, IncomeTransaction::count());
        $this->actingAs($customer, 'customer')->get(route('portal.orders.files.download', $finalFile))->assertRedirect();
    }

    public function test_a_customer_can_ask_for_changes_and_an_extra_revision_is_flagged(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        $order = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id, 'status' => ServiceOrder::DELIVERED, 'delivered_at' => now(), 'included_revisions' => 1]);
        $order->payments()->create(['kind' => 'dp', 'label' => 'Down payment', 'amount' => 100, 'income_transaction_id' => IncomeTransaction::factory()->create()->id]);
        $order->payments()->create(['kind' => 'final', 'label' => 'Final payment', 'amount' => 100]);
        $order->files()->create(['kind' => 'draft', 'path' => 'x/draft.xlsx', 'original_name' => 'draft.xlsx']);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.revisions.store', $order), ['message' => 'Change the colours'])->assertRedirect();
        $order->refresh();
        $this->assertSame(ServiceOrder::IN_PROGRESS, $order->status);
        $this->assertFalse($order->revisions->first()->is_extra);
        Mail::assertSent(ServiceOrderMail::class, fn ($mail) => str_contains($mail->mailSubject, 'Revision requested'));

        // While being revised, no further change request is accepted
        $this->actingAs($customer, 'customer')->post(route('portal.orders.revisions.store', $order), ['message' => 'Again'])->assertSessionHas('error');

        $this->actingAs($staff)->post(route('services.orders.deliver', $order))->assertSessionHas('status');
        $this->assertNotNull($order->revisions()->first()->resolved_at);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.revisions.store', $order), ['message' => 'One more thing'])->assertRedirect();
        $this->assertTrue($order->revisions()->get()->last()->is_extra, 'beyond the revisions the quote included');

        // Staff bill the extra revision
        $this->actingAs($staff)->post(route('services.orders.charges.store', $order), ['label' => 'Extra revision', 'amount' => 250000])->assertRedirect();
        $extra = $order->payments()->get()->last();
        $this->assertSame('extra', $extra->kind);
        $this->assertNotNull($extra->invoice_sent_at);
    }

    public function test_the_customer_can_decline_or_withdraw_but_not_cancel_agreed_work(): void
    {
        $customer = $this->customer();
        $this->staff();
        $quoted = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id]);
        $accepted = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id, 'status' => ServiceOrder::ACCEPTED]);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.decline', $quoted), ['decline_reason' => 'Too expensive'])->assertRedirect();
        $this->assertSame(ServiceOrder::DECLINED, $quoted->fresh()->status);
        $this->assertSame('Too expensive', $quoted->fresh()->decline_reason);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.cancel', $accepted))->assertSessionHas('error');
        $this->assertSame(ServiceOrder::ACCEPTED, $accepted->fresh()->status);
    }

    public function test_an_expired_quote_cannot_be_accepted(): void
    {
        $customer = $this->customer();
        $order = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id, 'quote_valid_until' => '2026-11-01']);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.accept', $order))->assertSessionHas('error');
        $this->assertSame(ServiceOrder::QUOTED, $order->fresh()->status);
        $this->assertSame(0, $order->payments()->count());
    }

    public function test_a_full_upfront_quote_creates_a_single_payment(): void
    {
        $customer = $this->customer();
        $order = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id, 'dp_percentage' => 100]);

        $this->actingAs($customer, 'customer')->post(route('portal.orders.accept', $order));

        $this->assertSame(1, $order->payments()->count());
        $this->assertEquals(2000000, $order->payments()->first()->amount);
    }

    public function test_staff_cancelling_drops_unpaid_invoices_and_keeps_paid_ones(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        $order = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id]);
        $this->actingAs($customer, 'customer')->post(route('portal.orders.accept', $order));
        $dp = $order->payments()->first();
        $this->actingAs($staff)->post(route('services.payments.pay', $dp), $this->payPayload());

        $this->actingAs($staff)->post(route('services.orders.cancel', $order))->assertRedirect();

        $this->assertSame(ServiceOrder::CANCELLED, $order->fresh()->status);
        $this->assertSame(1, $order->payments()->count(), 'the paid down payment stays on record');
        $this->assertSame(1, IncomeTransaction::count());
        Mail::assertSent(ServiceOrderMail::class, fn ($mail) => $mail->hasTo('budi@example.com') && str_contains($mail->mailSubject, 'Order cancelled'));
    }

    public function test_bank_transfer_payments_need_an_account(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        $order = ServiceOrder::factory()->quoted()->create(['customer_id' => $customer->id]);
        $this->actingAs($customer, 'customer')->post(route('portal.orders.accept', $order));
        $dp = $order->payments()->first();

        $this->actingAs($staff)->post(route('services.payments.pay', $dp), $this->payPayload(['payment_method' => 'Bank Transfer']))->assertSessionHasErrors('account_id');

        $account = Account::factory()->create(['is_active' => true]);
        $this->actingAs($staff)->post(route('services.payments.pay', $dp), $this->payPayload(['payment_method' => 'Bank Transfer', 'account_id' => $account->id, 'proof' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')]))->assertRedirect();

        $this->assertTrue($dp->fresh()->isPaid());
        $this->assertNotNull($dp->fresh()->proof_path);
    }

    // ---- Staff side ----

    public function test_staff_need_sales_permissions_and_payments_need_finance_manage(): void
    {
        $order = ServiceOrder::factory()->quoted()->create(['status' => ServiceOrder::ACCEPTED]);
        $order->payments()->create(['kind' => 'dp', 'label' => 'Down payment', 'amount' => 1000]);

        $this->actingAs($this->staff('Staff'))->get(route('services.orders.index'))->assertForbidden();
        $this->actingAs($this->staff('Viewer'))->get(route('services.orders.show', $order))->assertForbidden();

        $finance = $this->staff('Finance');
        $this->actingAs($finance)->get(route('services.orders.index'))->assertOk()->assertSee('Sales dashboard');
        $this->actingAs($finance)->get(route('services.orders.show', $order))->assertOk()->assertSee('Record payment');
        $this->actingAs($finance)->get(route('services.packages.index'))->assertOk();
    }

    public function test_packages_can_be_managed_with_add_ons_parsed_from_text(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('services.packages.store'), [
            'name' => 'Starter', 'starting_price' => 750000, 'included_revisions' => 1, 'is_active' => '1',
            'addons_text' => "Extra page | 300000\nBroken line\nAutomation | 500.000",
        ])->assertRedirect(route('services.packages.index'));

        $package = ServicePackage::firstOrFail();
        $this->assertEquals([['label' => 'Extra page', 'price' => 300000], ['label' => 'Automation', 'price' => 500000]], $package->addons);

        $this->actingAs($staff)->get(route('services.packages.edit', $package))->assertOk()->assertSee('Extra page | 300000');

        ServiceOrder::factory()->create(['service_package_id' => $package->id]);
        $this->actingAs($staff)->delete(route('services.packages.destroy', $package))->assertSessionHas('error');
        $this->assertNotNull($package->fresh());
    }

    public function test_the_quote_form_starts_from_the_package_price_and_ticked_add_ons(): void
    {
        $package = ServicePackage::factory()->create(['name' => 'Excel Dashboard Standard', 'starting_price' => 2500000]);
        $order = ServiceOrder::factory()->create(['service_package_id' => $package->id, 'addons' => [['label' => 'Extra page', 'price' => 300000]]]);

        $this->actingAs($this->staff())->get(route('services.orders.show', $order))
            ->assertOk()
            ->assertSee('Prepare the quote')
            ->assertSee('Excel Dashboard Standard')
            ->assertSee('2500000')
            ->assertSee('Extra page');
    }

    public function test_a_quote_total_must_be_more_than_zero(): void
    {
        $order = ServiceOrder::factory()->create();

        $this->actingAs($this->staff())->post(route('services.orders.quote', $order), $this->quote(['items' => [['label' => 'Free', 'amount' => 0]]]))->assertSessionHasErrors('items');
        $this->assertSame(ServiceOrder::BRIEF, $order->fresh()->status);
    }

    public function test_an_accepted_quote_can_no_longer_be_rewritten(): void
    {
        $order = ServiceOrder::factory()->quoted()->create(['status' => ServiceOrder::ACCEPTED]);

        $this->actingAs($this->staff())->post(route('services.orders.quote', $order), $this->quote())->assertSessionHas('error');
        $this->assertEquals(2000000, $order->fresh()->quote_total);
    }

    public function test_brief_files_can_be_downloaded_by_staff_but_not_deleted(): void
    {
        $order = ServiceOrder::factory()->create();
        Storage::disk('local')->put('service-orders/1/sample.xlsx', 'data');
        $file = $order->files()->create(['kind' => ServiceOrderFile::BRIEF, 'path' => 'service-orders/1/sample.xlsx', 'original_name' => 'sample.xlsx']);
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('services.files.download', $file))->assertOk();
        $this->actingAs($staff)->delete(route('services.files.destroy', $file))->assertForbidden();
    }

    public function test_the_portal_preview_is_read_only_for_orders(): void
    {
        $customer = $this->customer();
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('customer-portal.open', $customer));
        $this->post(route('portal.orders.store'), $this->brief())->assertRedirect(route('portal.dashboard'));

        $this->assertSame(0, ServiceOrder::count());
    }

    public function test_mails_render(): void
    {
        $order = ServiceOrder::factory()->quoted()->create();
        $order->payments()->create(['kind' => 'dp', 'label' => 'Down payment', 'amount' => 1000000]);

        foreach ([
            new ServiceOrderMail('Subject', 'Heading', ['A **line**'], ['Order' => 'SO-1'], 'Open', 'https://example.test', "BCA 123\na.n. PT ALF"),
            new ServiceOrderMail('Subject', 'Heading'),
        ] as $mail) {
            $html = $mail->render();
            $this->assertStringContainsString('Heading', $html);
        }
    }

    public function test_registration_emails_and_status_page_make_sense_for_a_custom_work_customer(): void
    {
        $customer = Customer::factory()->pendingApproval()->create(['customer_type' => 'individual', 'name' => 'Siti']);
        $customer->forceFill(['registration_track' => Customer::TRACK_SERVICE, 'status_token' => 'tok-123'])->save();
        ServiceOrder::factory()->create(['customer_id' => $customer->id, 'title' => 'Branch dashboard']);

        $this->assertStringContainsString('prepare a quote', (new CustomerRegistrationReceived($customer))->render());
        $this->assertStringContainsString('custom work', (new StaffRegistrationSubmitted($customer))->render());
        $this->assertStringContainsString('Branch dashboard', (new StaffRegistrationSubmitted($customer))->render());
        $this->get(route('customer-registration.status', 'tok-123'))->assertOk();

        $customer->forceFill(['registration_status' => Customer::REGISTRATION_COMPLETE, 'customer_code' => '261101'])->save();
        $approved = (new CustomerRegistrationApproved($customer))->render();
        $this->assertStringContainsString('send you a quote', $approved);
        $this->assertStringNotContainsString('Your program schedule will be shared soon', $approved);
    }
}
