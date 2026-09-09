<?php

namespace Tests\Unit;

use App\Actions\Auction\DetermineWinnersAction;
use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\ProcedureParticipant;
use App\Models\User;
use App\Services\AuctionSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Назначение победителей аукциона (фаза 8.10).
 */
class DetermineWinnersActionTest extends TestCase
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
     * @param WinnerMode $winnerMode Режим победителя
     * @return array{0: Procedure, 1: ProcedureLot, 2: ProcedureLot, 3: User, 4: User}
     */
    private function makeTwoLots(WinnerMode $winnerMode): array
    {
        $alice = User::factory()->create();
        $alice->assignRole('participant');
        $bob = User::factory()->create();
        $bob->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
        ]);
        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => $winnerMode,
            'only_admitted_from_rfp' => false,
            'is_paused' => false,
        ]);

        $lotA = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '100000.00',
        ]);
        $lotB = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '50000.00',
        ]);

        return [$procedure->fresh(['auctionSetting', 'lots']), $lotA, $lotB, $alice, $bob];
    }

    /**
     * @return void
     */
    public function test_per_lot_picks_lowest_bid_on_each_lot(): void
    {
        [$procedure, $lotA, $lotB, $alice, $bob] = $this->makeTwoLots(WinnerMode::PerLot);

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $alice->id,
            'amount' => '90000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $bob->id,
            'amount' => '88000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotB->id,
            'user_id' => $alice->id,
            'amount' => '40000.00',
        ]);

        app(DetermineWinnersAction::class)->execute($procedure);

        $this->assertSame($bob->id, $lotA->fresh()->winner_user_id);
        $this->assertSame($alice->id, $lotB->fresh()->winner_user_id);
        $this->assertTrue(
            ProcedureParticipant::query()
                ->where('procedure_id', $procedure->id)
                ->where('user_id', $bob->id)
                ->where('status', ParticipantStatus::Winner)
                ->exists(),
        );
    }

    /**
     * @return void
     */
    public function test_cancelled_bid_is_ignored(): void
    {
        [$procedure, $lotA, , $alice, $bob] = $this->makeTwoLots(WinnerMode::PerLot);

        AuctionBid::factory()->cancelled()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $bob->id,
            'amount' => '1000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $alice->id,
            'amount' => '99000.00',
        ]);

        app(DetermineWinnersAction::class)->execute($procedure);

        $this->assertSame($alice->id, $lotA->fresh()->winner_user_id);
    }

    /**
     * @return void
     */
    public function test_total_sum_assigns_same_winner_to_all_lots(): void
    {
        [$procedure, $lotA, $lotB, $alice, $bob] = $this->makeTwoLots(WinnerMode::TotalSum);

        // Alice: 90k + 45k = 135k; Bob: 92k + 40k = 132k → побеждает Bob (понижение)
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $alice->id,
            'amount' => '90000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotB->id,
            'user_id' => $alice->id,
            'amount' => '45000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $bob->id,
            'amount' => '92000.00',
        ]);
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotB->id,
            'user_id' => $bob->id,
            'amount' => '40000.00',
        ]);

        app(DetermineWinnersAction::class)->execute($procedure);

        $this->assertSame($bob->id, $lotA->fresh()->winner_user_id);
        $this->assertSame($bob->id, $lotB->fresh()->winner_user_id);
    }

    /**
     * @return void
     */
    public function test_finish_api_sets_winner_on_lot(): void
    {
        [$procedure, $lotA, , $alice] = $this->makeTwoLots(WinnerMode::PerLot);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $procedure->update(['responsible_user_id' => $admin->id]);

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lotA->id,
            'user_id' => $alice->id,
            'amount' => '91000.00',
        ]);

        app(AuctionSessionService::class)->finish($procedure->fresh(['auctionSetting', 'lots']), $admin);

        $this->assertSame(ProcedureStatus::Completed, $procedure->fresh()->status);
        $this->assertSame($alice->id, $lotA->fresh()->winner_user_id);
    }
}
