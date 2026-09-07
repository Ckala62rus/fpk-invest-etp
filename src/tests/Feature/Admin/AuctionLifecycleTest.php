<?php

namespace Tests\Feature\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты lifecycle аукциона (фаза 8.2).
 */
class AuctionLifecycleTest extends TestCase
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
     * @param User $admin Ответственный
     * @return Procedure
     */
    private function createPendingAuction(User $admin): Procedure
    {
        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::AuctionPending,
            'responsible_user_id' => $admin->id,
        ]);

        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
        ]);

        ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        return $procedure;
    }

    /**
     * @return void
     */
    public function test_trade_admin_can_run_full_lifecycle(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = $this->createPendingAuction($admin);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/start')
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('message', 'Аукцион запущен.');

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/pause')
            ->assertOk()
            ->assertJsonPath('data.auction_setting.is_paused', true);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/resume')
            ->assertOk()
            ->assertJsonPath('data.auction_setting.is_paused', false);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/finish')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * @return void
     */
    public function test_auditor_cannot_start_auction(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        /** @var User $admin */
        $admin = User::factory()->create();
        $procedure = $this->createPendingAuction($admin);

        $this->actingAs($auditor)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/start')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_cannot_start_draft_auction(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::Draft,
            'responsible_user_id' => $admin->id,
        ]);

        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
        ]);

        ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/auction/start')
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Запустить торги можно только из статуса «ожидает аукциона».',
            );
    }
}
