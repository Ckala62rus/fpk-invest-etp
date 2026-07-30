<?php

namespace Tests\Feature\Admin;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты приглашений участников ТЗП (фаза 5.5).
 */
class ProcedureParticipantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Приглашение participant в закрытую процедуру.
     *
     * @return void
     */
    public function test_can_invite_participant_to_closed_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'visibility' => ProcedureVisibility::Closed,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/participants', [
                'user_id' => $participant->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $participant->id)
            ->assertJsonPath('data.status', ParticipantStatus::Invited->value);

        $this->assertDatabaseHas('procedure_participants', [
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited->value,
        ]);
    }

    /**
     * Нельзя пригласить не-participant.
     *
     * @return void
     */
    public function test_cannot_invite_non_participant_role(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $procedure = Procedure::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/participants', [
                'user_id' => $auditor->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Приглашать можно только пользователей с ролью participant.');
    }

    /**
     * Допуск и отклонение участника.
     *
     * @return void
     */
    public function test_can_admit_and_reject_participant(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->create();

        $invite = $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/participants', [
                'user_id' => $participant->id,
            ])
            ->assertCreated();

        $id = $invite->json('data.id');

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/participants/'.$id, [
                'status' => ParticipantStatus::Admitted->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ParticipantStatus::Admitted->value)
            ->assertJsonPath('data.admitted_by', $admin->id);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/participants/'.$id, [
                'status' => ParticipantStatus::Rejected->value,
                'rejection_reason' => 'Неполный пакет документов',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ParticipantStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', 'Неполный пакет документов');
    }
}
