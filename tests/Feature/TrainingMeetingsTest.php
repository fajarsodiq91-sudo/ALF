<?php

namespace Tests\Feature;

use App\Models\TrainingSession;
use App\Models\TrainingSessionMeeting;
use App\Models\User;
use App\Services\MasterData;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TrainingMeetingsTest extends TestCase
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

    public function test_session_edit_page_lists_meetings_and_the_link_fields(): void
    {
        $session = TrainingSession::factory()->create();
        TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id, 'topic' => 'Topik Pertama']);

        $this->actingAs($this->financeUser())->get(route('training.edit', $session))
            ->assertOk()->assertSee('Topik Pertama')->assertSee('Learning Materials Link')->assertSee('Certificate Link');
    }

    public function test_add_toggle_and_delete_a_meeting(): void
    {
        $session = TrainingSession::factory()->create();
        $finance = $this->financeUser();

        $this->actingAs($finance)->post(route('training.meetings.store', $session), [
            'meeting_date' => '2026-10-05', 'start_time' => '09:00', 'end_time' => '12:00', 'location' => 'Ruang A', 'topic' => 'Dasar',
        ])->assertRedirect(route('training.edit', $session));
        $meeting = $session->meetings()->firstOrFail();
        $this->assertFalse($meeting->is_completed);
        $this->assertSame('09:00 – 12:00', $meeting->timeRange());

        Carbon::setTestNow('2026-10-05 13:00:00');
        $this->actingAs($finance)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();
        $this->assertTrue($meeting->fresh()->is_completed);
        $this->assertNotNull($meeting->fresh()->completed_at);

        $this->actingAs($finance)->patch(route('training.meetings.toggle', $meeting))->assertRedirect();
        $this->assertFalse($meeting->fresh()->is_completed);
        $this->assertNull($meeting->fresh()->completed_at);
        Carbon::setTestNow();

        $this->actingAs($finance)->delete(route('training.meetings.destroy', $meeting))->assertRedirect();
        $this->assertModelMissing($meeting);
    }

    public function test_meeting_validation_and_permissions(): void
    {
        $session = TrainingSession::factory()->create();
        $meeting = TrainingSessionMeeting::factory()->create(['training_session_id' => $session->id]);

        $this->actingAs($this->financeUser())->post(route('training.meetings.store', $session), [
            'meeting_date' => '', 'start_time' => '12:00', 'end_time' => '09:00',
        ])->assertSessionHasErrors(['meeting_date', 'end_time']);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp', 'training.view']);
        $this->actingAs($viewer)->post(route('training.meetings.store', $session), ['meeting_date' => '2026-10-05'])->assertForbidden();
        $this->actingAs($viewer)->patch(route('training.meetings.toggle', $meeting))->assertForbidden();
        $this->actingAs($viewer)->delete(route('training.meetings.destroy', $meeting))->assertForbidden();
    }

    public function test_certificate_and_materials_links_must_be_urls(): void
    {
        $session = TrainingSession::factory()->create();

        $this->actingAs($this->financeUser())->put(route('training.update', $session), [
            'training_program_id' => $session->training_program_id, 'start_date' => '2026-05-04', 'end_date' => '2026-05-05',
            'delivery_mode' => 'onsite', 'participants_count' => 1, 'fee' => 0, 'status' => 'planned',
            'materials_url' => 'bukan url', 'certificate_url' => 'https://ok.test/sertifikat',
        ])->assertSessionHasErrors('materials_url')->assertSessionDoesntHaveErrors('certificate_url');
    }

    public function test_program_type_is_a_master_data_option(): void
    {
        $this->assertSame(['learning' => 'Learning', 'consulting' => 'Consulting'], MasterData::options('program_type'));
    }
}
