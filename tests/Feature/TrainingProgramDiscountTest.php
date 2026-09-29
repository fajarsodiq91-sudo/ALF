<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingProgramDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
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

    // --- Model math --------------------------------------------------------

    public function test_percentage_discount_reduces_the_price(): void
    {
        $program = TrainingProgram::factory()->create(['standard_price' => 1_000_000])->fresh();
        $program->forceFill(['discount_type' => 'percentage', 'discount_value' => 20, 'discount_expires_at' => '2026-09-30'])->save();
        $program = $program->fresh();

        $this->assertTrue($program->hasActiveDiscount());
        $this->assertSame(200_000.0, $program->discountAmount());
        $this->assertSame(800_000.0, $program->finalPrice());
        $this->assertSame('20% off', $program->discountLabel());
    }

    public function test_fixed_discount_reduces_the_price_and_never_goes_below_zero(): void
    {
        $program = TrainingProgram::factory()->create(['standard_price' => 500_000]);
        $program->forceFill(['discount_type' => 'fixed', 'discount_value' => 750_000, 'discount_expires_at' => '2026-09-30'])->save();
        $program = $program->fresh();

        $this->assertSame(500_000.0, $program->discountAmount()); // clamped to the price itself
        $this->assertSame(0.0, $program->finalPrice());
        $this->assertSame('Rp 750.000 off', $program->discountLabel());
    }

    public function test_discount_stops_applying_after_it_expires(): void
    {
        $program = TrainingProgram::factory()->create(['standard_price' => 1_000_000]);
        $program->forceFill(['discount_type' => 'percentage', 'discount_value' => 20, 'discount_expires_at' => '2026-09-14'])->save();
        $program = $program->fresh();

        $this->assertFalse($program->hasActiveDiscount());
        $this->assertSame(0.0, $program->discountAmount());
        $this->assertSame(1_000_000.0, $program->finalPrice());
        $this->assertNull($program->discountLabel());
    }

    public function test_discount_is_active_through_the_end_of_its_expiry_date(): void
    {
        Carbon::setTestNow('2026-09-30 23:00:00');
        $program = TrainingProgram::factory()->create(['standard_price' => 1_000_000]);
        $program->forceFill(['discount_type' => 'percentage', 'discount_value' => 20, 'discount_expires_at' => '2026-09-30'])->save();

        $this->assertTrue($program->fresh()->hasActiveDiscount());
    }

    public function test_discount_without_expiry_date_never_expires(): void
    {
        $program = TrainingProgram::factory()->create(['standard_price' => 1_000_000]);
        $program->forceFill(['discount_type' => 'fixed', 'discount_value' => 100_000, 'discount_expires_at' => null])->save();

        $this->assertTrue($program->fresh()->hasActiveDiscount());
    }

    // --- Program form --------------------------------------------------------

    public function test_a_promo_can_be_set_when_creating_a_program(): void
    {
        $this->actingAs($this->financeUser())->post(route('training.programs.store'), [
            'name' => 'Excel Basic', 'program_type' => 'learning', 'duration_days' => 2, 'standard_price' => 1_000_000,
            'is_active' => '1', 'discount_type' => 'percentage', 'discount_value' => 15, 'discount_expires_at' => '2026-10-01',
        ])->assertRedirect(route('training.programs.index'));

        $program = TrainingProgram::firstOrFail();
        $this->assertSame('percentage', $program->discount_type);
        $this->assertSame(15.0, (float) $program->discount_value);
        $this->assertSame('2026-10-01', $program->discount_expires_at->toDateString());
    }

    public function test_discount_value_and_expiry_are_required_once_a_discount_type_is_chosen(): void
    {
        $this->actingAs($this->financeUser())->post(route('training.programs.store'), [
            'name' => 'Excel Basic', 'program_type' => 'learning', 'duration_days' => 2, 'standard_price' => 1_000_000,
            'is_active' => '1', 'discount_type' => 'percentage',
        ])->assertSessionHasErrors(['discount_value', 'discount_expires_at']);

        $this->assertSame(0, TrainingProgram::count());
    }

    public function test_percentage_discount_cannot_exceed_100(): void
    {
        $this->actingAs($this->financeUser())->post(route('training.programs.store'), [
            'name' => 'Excel Basic', 'program_type' => 'learning', 'duration_days' => 2, 'standard_price' => 1_000_000,
            'is_active' => '1', 'discount_type' => 'percentage', 'discount_value' => 150, 'discount_expires_at' => '2026-10-01',
        ])->assertSessionHasErrors('discount_value');
    }

    public function test_clearing_the_discount_type_removes_the_promo(): void
    {
        $finance = $this->financeUser();
        $program = TrainingProgram::factory()->withDiscount()->create(['standard_price' => 1_000_000]);

        $this->actingAs($finance)->put(route('training.programs.update', $program), [
            'name' => $program->name, 'program_type' => $program->program_type, 'duration_days' => $program->duration_days,
            'standard_price' => 1_000_000, 'is_active' => '1', 'discount_type' => '',
        ])->assertRedirect(route('training.programs.index'));

        $program = $program->fresh();
        $this->assertNull($program->discount_type);
        $this->assertFalse($program->hasActiveDiscount());
    }

    public function test_programs_index_shows_the_discounted_price(): void
    {
        $active = TrainingProgram::factory()->withDiscount('percentage', 25)->create(['name' => 'Promo Program', 'standard_price' => 1_000_000]);
        $expired = TrainingProgram::factory()->create(['name' => 'Old Promo Program', 'standard_price' => 1_000_000]);
        $expired->forceFill(['discount_type' => 'fixed', 'discount_value' => 100_000, 'discount_expires_at' => '2026-01-01'])->save();

        $response = $this->actingAs($this->financeUser())->get(route('training.programs.index'));

        $response->assertOk()
            ->assertSee('750.000') // discounted price for the active promo
            ->assertSee('25% off');

        // The expired promo no longer shows a strikethrough / discounted price.
        $this->assertStringNotContainsString('Rp 900.000', $response->getContent());
    }

    // --- Public registration & downstream pricing ---------------------------

    private function token(): string
    {
        $customer = Customer::factory()->awaitingCustomer()->create();

        return $customer->issueRegistrationToken();
    }

    public function test_public_registration_form_shows_the_discounted_price(): void
    {
        $program = TrainingProgram::factory()->withDiscount('percentage', 20)->create(['standard_price' => 1_000_000]);
        $token = $this->token();

        $response = $this->get(route('customer-registration.show', $token));

        $response->assertOk();
        $response->assertSee('"'.$program->id.'":800000', false); // discounted price passed to Alpine
        $response->assertSee('"'.$program->id.'":1000000', false); // original price for the strikethrough
    }

    public function test_requested_program_summary_uses_the_discounted_price(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->withDiscount('fixed', 300_000)->create(['standard_price' => 1_000_000]);
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), [
            'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => '0812',
            'programs' => [[
                'training_program_id' => $program->id,
                'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']],
            ]],
        ]);

        $customer = Customer::firstOrFail();
        $summary = $customer->requestedProgramSummaries();

        $this->assertSame(700_000.0, $summary[0]['price']);
    }

    public function test_sales_review_prefills_fee_with_the_discounted_price(): void
    {
        Mail::fake();
        $program = TrainingProgram::factory()->withDiscount('percentage', 10)->create(['name' => 'Power BI Dasar', 'standard_price' => 1_000_000]);
        $token = $this->token();

        $this->post(route('customer-registration.store', $token), [
            'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => '0812',
            'programs' => [[
                'training_program_id' => $program->id,
                'meetings' => [['meeting_date' => '2026-10-06', 'start_time' => '20:00', 'end_time' => '21:30']],
            ]],
        ]);
        $customer = Customer::firstOrFail();

        $this->actingAs($this->financeUser())->get(route('sales.review', $customer))
            ->assertOk()
            ->assertSee('"fee":"900000"', false);
    }
}
