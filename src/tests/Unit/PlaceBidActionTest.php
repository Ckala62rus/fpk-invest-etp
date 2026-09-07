<?php

namespace Tests\Unit;

use App\Actions\Auction\PlaceBidAction;
use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Enums\WinnerMode;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тяжёлые unit-тесты подачи ставки (фаза 8.3).
 */
class PlaceBidActionTest extends TestCase
{
    use RefreshDatabase;

    private PlaceBidAction $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->action = app(PlaceBidAction::class);
    }

    /**
     * @param array<string, mixed> $settingOverrides
     * @return array{0: Procedure, 1: ProcedureLot, 2: User}
     */
    private function makeOpenInProgressAuction(array $settingOverrides = []): array
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Open,
            'starts_at' => now()->subHour(),
        ]);

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

        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '100000.00',
            'bid_step' => '1000.00',
            'current_price' => null,
        ]);

        return [$procedure->fresh(['auctionSetting']), $lot, $participant];
    }

    /**
     * @return void
     */
    public function test_places_decreasing_bid_and_updates_current_price(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction();

        $bid = $this->action->execute($procedure, $lot, $participant, '99000.00', '127.0.0.1');

        $this->assertSame('99000.00', $bid->amount);
        $this->assertSame('99000.00', $lot->fresh()->current_price);
        $this->assertFalse($bid->is_cancelled);
    }

    /**
     * @return void
     */
    public function test_rejects_bid_not_lower_on_decrease_auction(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction();

        $this->expectException(DomainException::class);
        $this->action->execute($procedure, $lot, $participant, '100000.00');
    }

    /**
     * @return void
     */
    public function test_step_minimum_enforced(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction([
            'bid_mode' => BidMode::StepMinimum,
        ]);

        $this->expectException(DomainException::class);
        $this->action->execute($procedure, $lot, $participant, '99500.00');
    }

    /**
     * @return void
     */
    public function test_forbid_equal_bids(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction();
        $other = User::factory()->create();
        $other->assignRole('participant');

        $this->action->execute($procedure, $lot, $participant, '98000.00');

        $this->expectException(DomainException::class);
        $this->action->execute($procedure->fresh(['auctionSetting']), $lot->fresh(), $other, '98000.00');
    }

    /**
     * @return void
     */
    public function test_rejects_when_paused(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction(['is_paused' => true]);

        $this->expectException(DomainException::class);
        $this->action->execute($procedure->fresh(['auctionSetting']), $lot, $participant, '99000.00');
    }

    /**
     * @return void
     */
    public function test_closed_auction_requires_invitation(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Closed,
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
            'start_price' => '100000.00',
            'bid_step' => '1000.00',
        ]);

        $this->expectException(DomainException::class);
        $this->action->execute($procedure->fresh(['auctionSetting']), $lot, $participant, '99000.00');
    }

    /**
     * @return void
     */
    public function test_closed_auction_allows_invited_participant(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Closed,
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

        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '100000.00',
            'bid_step' => '1000.00',
        ]);

        $bid = $this->action->execute($procedure->fresh(['auctionSetting']), $lot, $participant, '99000.00');
        $this->assertNotNull($bid->id);
    }

    /**
     * @return void
     */
    public function test_increase_auction_requires_higher_amount(): void
    {
        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction([
            'auction_mode' => AuctionMode::Increase,
        ]);

        $bid = $this->action->execute($procedure, $lot, $participant, '101000.00');
        $this->assertSame('101000.00', $bid->amount);

        $this->expectException(DomainException::class);
        $this->action->execute($procedure->fresh(['auctionSetting']), $lot->fresh(), $participant, '100500.00');
    }

    /**
     * Ставка в окне продления сдвигает ends_at.
     *
     * @return void
     */
    public function test_bid_near_deadline_extends_ends_at(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Open,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMinutes(2),
        ]);

        $procedure->auctionSetting()->create([
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'extension_trigger_minutes' => 3,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
            'is_paused' => false,
        ]);

        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '100000.00',
            'bid_step' => '1000.00',
        ]);

        $originalEndsAt = $procedure->ends_at->copy();

        $this->action->execute(
            $procedure->fresh(['auctionSetting']),
            $lot,
            $participant,
            '99000.00',
        );

        $this->assertTrue(
            $procedure->fresh()->ends_at->equalTo($originalEndsAt->addMinutes(5)),
        );
    }
}
