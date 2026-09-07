<?php

namespace Tests\Unit;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use App\Services\AuctionSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit/feature-гибрид state machine аукциона (фаза 8.2).
 */
class AuctionSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuctionSessionService $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = app(AuctionSessionService::class);
    }

    /**
     * @return array{0: Procedure, 1: User}
     */
    private function makeAuctionPending(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

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
            'is_paused' => false,
        ]);

        ProcedureLot::factory()->create(['procedure_id' => $procedure->id]);

        return [$procedure->fresh(['auctionSetting', 'lots']), $admin];
    }

    /**
     * @return void
     */
    public function test_start_pause_resume_finish_happy_path(): void
    {
        [$procedure, $admin] = $this->makeAuctionPending();

        $started = $this->service->start($procedure, $admin);
        $this->assertSame(ProcedureStatus::InProgress, $started->status);
        $this->assertTrue($this->service->isAcceptingBids($started));

        $paused = $this->service->pause($started, $admin);
        $this->assertTrue($paused->auctionSetting->is_paused);
        $this->assertFalse($this->service->isAcceptingBids($paused));

        $resumed = $this->service->resume($paused, $admin);
        $this->assertFalse($resumed->auctionSetting->is_paused);
        $this->assertTrue($this->service->isAcceptingBids($resumed));

        $finished = $this->service->finish($resumed, $admin);
        $this->assertSame(ProcedureStatus::Completed, $finished->status);
        $this->assertNotNull($finished->completed_at);
        $this->assertFalse($this->service->isAcceptingBids($finished));
    }

    /**
     * @return void
     */
    public function test_cannot_start_without_lots(): void
    {
        $admin = User::factory()->create();
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

        $this->expectException(DomainException::class);
        $this->service->start($procedure, $admin);
    }

    /**
     * @return void
     */
    public function test_cannot_pause_twice(): void
    {
        [$procedure, $admin] = $this->makeAuctionPending();
        $started = $this->service->start($procedure, $admin);
        $this->service->pause($started, $admin);

        $this->expectException(DomainException::class);
        $this->service->pause($started->fresh(['auctionSetting']), $admin);
    }
}
