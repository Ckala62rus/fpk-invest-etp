<?php

namespace Tests\Unit;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use App\Services\AuctionTimerService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-тесты автопродления и простоя аукциона (фаза 8.4).
 */
class AuctionTimerServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuctionTimerService $timer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->timer = new AuctionTimerService;
    }

    /**
     * @param array<string, mixed> $procedureOverrides
     * @param array<string, mixed> $settingOverrides
     * @return Procedure
     */
    private function makeAuction(array $procedureOverrides = [], array $settingOverrides = []): Procedure
    {
        $procedure = Procedure::factory()->auction()->create(array_merge([
            'status' => ProcedureStatus::InProgress,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMinutes(10),
        ], $procedureOverrides));

        $procedure->auctionSetting()->create(array_merge([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'extension_trigger_minutes' => 3,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
            'is_paused' => false,
        ], $settingOverrides));

        return $procedure->fresh(['auctionSetting']);
    }

    /**
     * @return void
     */
    public function test_does_not_extend_when_far_from_deadline(): void
    {
        $procedure = $this->makeAuction(['ends_at' => now()->addHour()]);

        $this->assertFalse($this->timer->shouldExtend($procedure, $procedure->auctionSetting));
        $this->assertNull($this->timer->extendIfNeeded($procedure, $procedure->auctionSetting));
    }

    /**
     * @return void
     */
    public function test_extends_when_within_trigger_window(): void
    {
        $procedure = $this->makeAuction(['ends_at' => now()->addMinutes(2)]);
        $original = $procedure->ends_at->copy();

        $newEndsAt = $this->timer->extendIfNeeded($procedure, $procedure->auctionSetting);

        $this->assertNotNull($newEndsAt);
        $this->assertTrue($newEndsAt->equalTo($original->addMinutes(5)));
        $this->assertTrue($procedure->fresh()->ends_at->equalTo($newEndsAt));
    }

    /**
     * @return void
     */
    public function test_null_trigger_uses_extension_minutes_as_window(): void
    {
        $procedure = $this->makeAuction(
            ['ends_at' => now()->addMinutes(4)],
            ['extension_trigger_minutes' => null, 'extension_minutes' => 5],
        );

        $this->assertTrue($this->timer->shouldExtend($procedure, $procedure->auctionSetting));
    }

    /**
     * @return void
     */
    public function test_idle_timeout_without_bids_uses_starts_at(): void
    {
        $procedure = $this->makeAuction([
            'starts_at' => now()->subMinutes(31),
            'ends_at' => now()->addHour(),
        ]);

        $this->assertTrue($this->timer->isIdleTimedOut($procedure, $procedure->auctionSetting));
        $this->assertTrue($this->timer->shouldAutoFinish($procedure));
    }

    /**
     * @return void
     */
    public function test_idle_resets_on_recent_bid(): void
    {
        $procedure = $this->makeAuction([
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHour(),
        ]);

        $lot = ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'user_id' => User::factory(),
            'created_at' => now()->subMinutes(5),
            'is_cancelled' => false,
        ]);

        $this->assertFalse($this->timer->isIdleTimedOut($procedure, $procedure->auctionSetting));
        $this->assertFalse($this->timer->shouldAutoFinish($procedure));
    }

    /**
     * idle_timeout_minutes = 0 отключает автозавершение по простою.
     *
     * @return void
     */
    public function test_idle_timeout_zero_disables_idle_finish(): void
    {
        $procedure = $this->makeAuction(
            [
                'starts_at' => now()->subHours(3),
                'ends_at' => now()->addDay(),
            ],
            ['idle_timeout_minutes' => 0],
        );

        $this->assertFalse($this->timer->isIdleTimedOut($procedure, $procedure->auctionSetting));
        $this->assertFalse($this->timer->shouldAutoFinish($procedure));
    }

    /**
     * @return void
     */
    public function test_paused_auction_is_not_auto_finished(): void
    {
        $procedure = $this->makeAuction(
            ['starts_at' => now()->subHours(2), 'ends_at' => now()->subMinute()],
            ['is_paused' => true],
        );

        $this->assertFalse($this->timer->shouldAutoFinish($procedure));
    }
}
