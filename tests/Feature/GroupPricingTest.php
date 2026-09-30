<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GroupPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-09-15 10:00:00'); // a Tuesday
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

    private function excel(array $overrides = []): TrainingProgram
    {
        return TrainingProgram::factory()->create([
            'name' => 'Intermediate Excel',
            'standard_price' => 750000,
            'group_max_size' => 5,
            'group_tiers' => [['min' => 4, 'price' => 500000], ['min' => 2, 'price' => 650000]],
            ...$overrides,
        ]);
    }

    public function test_price_per_person_steps_down_with_group_size(): void
    {
        $program = $this->excel();

        $this->assertSame(750000.0, $program->pricePerPerson(1));
        $this->assertSame(650000.0, $program->pricePerPerson(2));
        $this->assertSame(650000.0, $program->pricePerPerson(3));
        $this->assertSame(500000.0, $program->pricePerPerson(4));
        $this->assertSame(500000.0, $program->pricePerPerson(5));
        $this->assertSame(1950000.0, $program->groupTotal(3));
        $this->assertSame(2500000.0, $program->groupTotal(5));
        $this->assertSame(2500000.0, $program->groupTotal(9), 'the group size is capped at the maximum');
    }

    public function test_a_program_without_group_pricing_is_unchanged(): void
    {
        $program = TrainingProgram::factory()->create(['standard_price' => 750000]);

        $this->assertFalse($program->allowsGroups());
        $this->assertSame(750000.0, $program->groupTotal(3));
    }

    public function test_the_promo_applies_on_top_of_the_tier_price(): void
    {
        $program = $this->excel(['discount_type' => 'percentage', 'discount_value' => 10, 'discount_expires_at' => '2026-12-31']);

        $this->assertEqualsWithDelta(585000.0, $program->pricePerPerson(2), 0.01);
    }

    public function test_admin_can_change_the_tiers_at_any_time(): void
    {
        $program = $this->excel();

        $this->actingAs($this->financeUser())->put(route('training.programs.update', $program), [
            'name' => 'Intermediate Excel', 'program_type' => 'learning', 'duration_days' => 1, 'standard_price' => 800000,
            'group_max_size' => 4,
            'group_tiers' => [['min' => 2, 'price' => 700000], ['min' => '', 'price' => ''], ['min' => 3, 'price' => 550000]],
        ])->assertSessionHasNoErrors();

        $program->refresh();
        $this->assertSame(4, $program->maxGroupSize());
        $this->assertSame(550000.0, $program->pricePerPerson(4));
        $this->assertSame(700000.0, $program->pricePerPerson(2));
        $this->assertSame(800000.0, $program->pricePerPerson(1));
    }

    public function test_tiers_are_validated(): void
    {
        $program = $this->excel();
        $base = ['name' => 'X', 'program_type' => 'learning', 'duration_days' => 1, 'standard_price' => 800000, 'group_max_size' => 5];

        $this->actingAs($this->financeUser())->put(route('training.programs.update', $program), [...$base, 'group_tiers' => [['min' => 1, 'price' => 1]]])
            ->assertSessionHasErrors('group_tiers.0.min');
        $this->actingAs($this->financeUser())->put(route('training.programs.update', $program), [...$base, 'group_tiers' => [['min' => 2, 'price' => 1], ['min' => 2, 'price' => 2]]])
            ->assertSessionHasErrors('group_tiers.0.min');
        $this->actingAs($this->financeUser())->put(route('training.programs.update', $program), [...$base, 'group_tiers' => [['min' => 9, 'price' => 1]]])
            ->assertSessionHasErrors('group_tiers.0.min');
    }

    /** @return array<string, mixed> */
    private function register(string $type, TrainingProgram $program, int $size)
    {
        $this->actingAs($this->financeUser())->post(route('sales.invite.store'), ['customer_type' => $type]);
        $token = Customer::firstOrFail()->registration_token;

        return $this->post(route('customer-registration.store', $token), [
            'name' => 'Ani', 'email' => 'ani@test.id', 'phone' => '0812', 'terms_accepted' => '1',
            'programs' => [['training_program_id' => $program->id, 'group_size' => $size, 'meetings' => [
                ['meeting_date' => '2026-09-22', 'start_time' => '20:00', 'end_time' => '21:30'],
            ]]],
        ]);
    }

    public function test_an_individual_registers_a_group_and_the_total_follows_the_tier(): void
    {
        Mail::fake();
        $program = $this->excel();

        $this->register('individual', $program, 3)->assertSessionHasNoErrors();

        $summary = Customer::firstOrFail()->requestedProgramSummaries()[0];
        $this->assertSame(3, $summary['group_size']);
        $this->assertSame(650000.0, $summary['per_person']);
        $this->assertSame(1950000.0, $summary['price']);
    }

    public function test_a_group_larger_than_the_maximum_is_refused(): void
    {
        Mail::fake();

        $this->register('individual', $this->excel(), 6)->assertSessionHasErrors('programs.0.group_size');
    }

    public function test_only_individuals_can_register_as_a_group(): void
    {
        Mail::fake();

        $this->register('company', $this->excel(), 2)->assertSessionHasErrors('programs.0.group_size');
    }

    public function test_approval_prefills_the_group_fee_and_friends_join_with_their_own_ids(): void
    {
        Mail::fake();
        $program = $this->excel();
        $this->register('individual', $program, 3);
        $owner = Customer::firstOrFail();
        $finance = $this->financeUser();

        $this->actingAs($finance)->get(route('sales.review', $owner))->assertOk()->assertSee('Group size', false)->assertSee('1950000', false);

        $this->actingAs($finance)->post(route('sales.approve', $owner), ['programs' => [[
            'training_program_id' => $program->id, 'delivery_mode' => 'onsite', 'fee' => 1950000, 'participant_limit' => 3,
            'meetings' => [['meeting_date' => '2026-09-22', 'start_time' => '20:00', 'end_time' => '21:30']],
        ]]]);

        $session = TrainingSession::firstOrFail();
        $this->assertSame('1950000.00', $session->fee);
        $this->assertNotNull($session->participant_token);
        auth()->logout();

        $this->actingAs($finance)->get(route('training.edit', $session))->assertOk()->assertSee('<svg', false)->assertSee('Scan to open the participant registration form');
        auth()->logout();
        $this->assertTrue($session->participants->contains($owner), 'the first registrant already counts as joined');
        $this->assertSame(1, $session->participants->count());

        $this->post(route('participant.store', $session->participant_token), ['name' => 'X', 'email' => 'x@test.id'])->assertSessionHasErrors('phone');

        foreach (['b@test.id', 'c@test.id'] as $email) {
            $this->post(route('participant.store', $session->participant_token), ['name' => $email, 'email' => $email, 'phone' => '0812', 'city' => 'Bandung', 'address' => 'Jl. A'])->assertSessionHasNoErrors();
        }
        $this->post(route('participant.store', $session->participant_token), ['name' => 'D', 'email' => 'd@test.id', 'phone' => '0812'])->assertSessionHasErrors('name');

        $codes = Customer::whereIn('email', ['ani@test.id', 'b@test.id', 'c@test.id'])->pluck('customer_code');
        $this->assertCount(3, $codes->unique());
        $this->assertSame(3, $session->fresh()->participants_count);

        $friend = Customer::where('email', 'b@test.id')->firstOrFail();
        $this->assertSame('Bandung', $friend->city);
        $this->actingAs($finance)->get(route('sales.index'))->assertSee('b@test.id')->assertSee('c@test.id');
    }
}
