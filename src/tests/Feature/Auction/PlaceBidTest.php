<?php

namespace Tests\Feature\Auction;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Enums\WinnerMode;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты API подачи ставок (фаза 8.3).
 */
class PlaceBidTest extends TestCase
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
    public function test_participant_can_place_bid(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Open,
        ]);

        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
            'is_paused' => false,
        ]);

        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '50000.00',
            'bid_step' => '500.00',
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids', [
                'amount' => 49000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', '49000.00')
            ->assertJsonPath('message', 'Ставка принята.');

        $this->actingAs($participant)
            ->getJson('/api/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @return void
     */
    public function test_guest_cannot_place_bid(): void
    {
        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
        ]);
        $lot = ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        $this->postJson('/api/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids', [
            'amount' => 1000,
        ])->assertUnauthorized();
    }
}
