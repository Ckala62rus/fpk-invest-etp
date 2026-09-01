<?php

namespace Tests\Feature\Admin;

use App\Enums\ParticipantStatus;
use App\Jobs\SendExternalInvitesJob;
use App\Models\ExternalInviteBatch;
use App\Models\Procedure;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature-тесты внешних email-приглашений (фаза 6.8).
 */
class ExternalInviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * trade_admin запускает рассылку с дедупликацией.
     *
     * @return void
     */
    public function test_trade_admin_can_send_external_invites(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'responsible_user_id' => $admin->id,
        ]);

        $participant = User::factory()->create(['email' => 'existing@test.test']);
        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/external-invites', [
                'emails' => [
                    'new@test.test',
                    'existing@test.test',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.emails.0', 'new@test.test');

        Queue::assertPushed(SendExternalInvitesJob::class);

        $this->assertDatabaseHas('external_invite_batches', [
            'procedure_id' => $procedure->id,
        ]);
    }

    /**
     * Повторная рассылка на тот же email пропускается.
     *
     * @return void
     */
    public function test_duplicate_emails_are_skipped(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create();

        ExternalInviteBatch::query()->create([
            'procedure_id' => $procedure->id,
            'emails' => ['already@test.test'],
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/external-invites', [
                'emails' => ['already@test.test'],
            ])
            ->assertStatus(422);
    }
}
