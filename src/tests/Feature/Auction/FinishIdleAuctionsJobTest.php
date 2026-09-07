<?php

namespace Tests\Feature\Auction;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Jobs\FinishIdleAuctionsJob;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты автозавершения аукциона по простою (фаза 8.5).
 */
class FinishIdleAuctionsJobTest extends TestCase
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
     * @param array<string, mixed> $procedureOverrides
     * @param array<string, mixed> $settingOverrides
     * @return Procedure
     */
    private function makeInProgress(array $procedureOverrides = [], array $settingOverrides = []): Procedure
    {
        $procedure = Procedure::factory()->auction()->create(array_merge([
            'status' => ProcedureStatus::InProgress,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHour(),
        ], $procedureOverrides));

        $procedure->auctionSetting()->create(array_merge([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
            'is_paused' => false,
        ], $settingOverrides));

        ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        return $procedure->fresh(['auctionSetting']);
    }

    /**
     * @return void
     */
    public function test_finishes_idle_auction(): void
    {
        $procedure = $this->makeInProgress();

        (new FinishIdleAuctionsJob)->handle(
            app(\App\Services\AuctionTimerService::class),
            app(\App\Services\AuctionSessionService::class),
        );

        $this->assertSame(ProcedureStatus::Completed, $procedure->fresh()->status);
        $this->assertNotNull($procedure->fresh()->completed_at);
    }

    /**
     * @return void
     */
    public function test_does_not_finish_when_recent_bid_exists(): void
    {
        $procedure = $this->makeInProgress();
        $lot = $procedure->lots()->firstOrFail();

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'user_id' => User::factory(),
            'created_at' => now()->subMinutes(5),
        ]);

        (new FinishIdleAuctionsJob)->handle(
            app(\App\Services\AuctionTimerService::class),
            app(\App\Services\AuctionSessionService::class),
        );

        $this->assertSame(ProcedureStatus::InProgress, $procedure->fresh()->status);
    }

    /**
     * @return void
     */
    public function test_finishes_when_deadline_passed(): void
    {
        $procedure = $this->makeInProgress([
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->subMinute(),
        ]);

        $lot = $procedure->lots()->firstOrFail();
        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'created_at' => now()->subMinutes(2),
        ]);

        (new FinishIdleAuctionsJob)->handle(
            app(\App\Services\AuctionTimerService::class),
            app(\App\Services\AuctionSessionService::class),
        );

        $this->assertSame(ProcedureStatus::Completed, $procedure->fresh()->status);
    }
}
