<?php

namespace Tests\Feature\Admin;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Jobs\SendProcedurePublishedMailsJob;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature-тесты публикации ТЗП (фаза 5.7).
 */
class ProcedurePublishTest extends TestCase
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
     * Публикация КП: draft → accepting + Job в очередь.
     *
     * @return void
     */
    public function test_can_publish_rfp_draft(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'type' => ProcedureType::RequestForProposal,
            'status' => ProcedureStatus::Draft,
            'ends_at' => now()->addDays(7),
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', ProcedureStatus::Accepting->value)
            ->assertJsonPath('message', 'Процедура опубликована.');

        $this->assertNotNull($procedure->fresh()->published_at);

        Queue::assertPushed(SendProcedurePublishedMailsJob::class);
    }

    /**
     * Аукцион без лотов нельзя опубликовать.
     *
     * @return void
     */
    public function test_cannot_publish_auction_without_lots(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create([
            'type' => ProcedureType::Auction,
            'status' => ProcedureStatus::Draft,
            'ends_at' => now()->addDays(3),
        ]);

        AuctionSetting::query()->create([
            'procedure_id' => $procedure->id,
            'bid_mode' => 'standard',
            'auction_mode' => 'decrease',
            'winner_mode' => 'per_lot',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/publish')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Для аукциона добавьте хотя бы один лот.');
    }

    /**
     * Закрытая процедура без участников — 422.
     *
     * @return void
     */
    public function test_closed_procedure_requires_participants(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create([
            'status' => ProcedureStatus::Draft,
            'visibility' => ProcedureVisibility::Closed,
            'ends_at' => now()->addDays(5),
            'type' => ProcedureType::RequestForProposal,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/publish')
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Для закрытой процедуры пригласите хотя бы одного участника.',
            );
    }

    /**
     * Закрытый аукцион с лотом и участником публикуется.
     *
     * @return void
     */
    public function test_can_publish_closed_auction_with_lot_and_participant(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->create([
            'type' => ProcedureType::Auction,
            'status' => ProcedureStatus::Draft,
            'visibility' => ProcedureVisibility::Closed,
            'ends_at' => now()->addDays(3),
        ]);

        AuctionSetting::query()->create([
            'procedure_id' => $procedure->id,
            'bid_mode' => 'standard',
            'auction_mode' => 'decrease',
            'winner_mode' => 'per_lot',
        ]);

        ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', ProcedureStatus::AuctionPending->value);

        Queue::assertPushed(SendProcedurePublishedMailsJob::class);
    }
}
