<?php

namespace Tests\Feature;

use App\Services\OperatingHours;
use App\Services\SessionPaymentPlan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgramSessionMinutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_length_allows_free_start_times_inside_a_window(): void
    {
        OperatingHours::save([1 => [['start' => '09:00', 'end' => '16:00']]], true);
        $monday = '2026-10-05';

        $this->assertNull(OperatingHours::violation($monday, '10:30', '11:30', 60));
        $this->assertNull(OperatingHours::violation($monday, '09:00', '16:00', 420));
        $this->assertNotNull(OperatingHours::violation($monday, '15:30', '16:30', 60)); // past closing
        $this->assertNotNull(OperatingHours::violation($monday, '10:00', '10:45', 60)); // wrong length
        $this->assertNotNull(OperatingHours::violation($monday, '10:10', '11:10', 60)); // off the 30-minute grid
        $this->assertNotNull(OperatingHours::violation($monday, '10:00', '11:00')); // no session length: whole window only
    }

    public function test_a_420_minute_single_meeting_program_may_still_pay_50_50(): void
    {
        $this->assertSame('installment', SessionPaymentPlan::effective('installment', 1, 420));
        $this->assertSame('full', SessionPaymentPlan::effective('installment', 1, 60));
        $this->assertCount(2, SessionPaymentPlan::preview(1000000, 'installment', 1, 420));
        $this->assertCount(1, SessionPaymentPlan::preview(1000000, 'installment', 1, 60));
    }
}
