<?php

namespace Tests\Feature\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты API настроек аукциона (фаза 8.1).
 */
class AuctionSettingTest extends TestCase
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
     * @return void
     */
    public function test_guest_cannot_read_auction_settings(): void
    {
        $procedure = Procedure::factory()->auction()->create();

        $this->getJson('/api/admin/procedures/'.$procedure->id.'/auction-settings')
            ->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_trade_admin_can_read_and_update_own_auction_settings(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::Draft,
            'responsible_user_id' => $admin->id,
        ]);

        // При create auction через factory настройки могут отсутствовать — создаём через Action create defaults path
        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/auction-settings')
            ->assertOk()
            ->assertJsonPath('data.bid_mode', 'standard')
            ->assertJsonPath('data.auction_mode', 'decrease');

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/auction-settings', [
                'bid_mode' => BidMode::StepMinimum->value,
                'auction_mode' => AuctionMode::Increase->value,
                'extension_minutes' => 10,
                'extension_trigger_minutes' => 3,
                'idle_timeout_minutes' => 45,
                'forbid_equal_bids' => false,
                'winner_mode' => WinnerMode::TotalSum->value,
                'only_admitted_from_rfp' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.bid_mode', 'step_minimum')
            ->assertJsonPath('data.auction_mode', 'increase')
            ->assertJsonPath('data.extension_minutes', 10)
            ->assertJsonPath('data.idle_timeout_minutes', 45)
            ->assertJsonPath('data.forbid_equal_bids', false)
            ->assertJsonPath('data.winner_mode', 'total_sum')
            ->assertJsonPath('data.only_admitted_from_rfp', true);

        $this->assertDatabaseHas('auction_settings', [
            'procedure_id' => $procedure->id,
            'bid_mode' => BidMode::StepMinimum->value,
            'auction_mode' => AuctionMode::Increase->value,
            'extension_minutes' => 10,
        ]);
    }

    /**
     * @return void
     */
    public function test_cannot_update_settings_for_rfp_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create([
            'status' => ProcedureStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/auction-settings', [
                'extension_minutes' => 7,
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Настройки аукциона доступны только для процедур типа auction.',
            );
    }

    /**
     * @return void
     */
    public function test_cannot_update_settings_after_auction_started(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
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

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/auction-settings', [
                'extension_minutes' => 15,
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Настройки аукциона можно менять только до начала торгов (черновик или ожидание аукциона).',
            );
    }

    /**
     * @return void
     */
    public function test_auditor_can_read_but_not_update(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::Draft,
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

        $this->actingAs($auditor)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/auction-settings')
            ->assertOk();

        $this->actingAs($auditor)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/auction-settings', [
                'extension_minutes' => 8,
            ])
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_can_update_settings_in_auction_pending(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::AuctionPending,
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

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/auction-settings', [
                'idle_timeout_minutes' => 60,
            ])
            ->assertOk()
            ->assertJsonPath('data.idle_timeout_minutes', 60);
    }
}
