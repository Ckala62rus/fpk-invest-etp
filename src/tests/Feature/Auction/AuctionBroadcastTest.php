<?php

namespace Tests\Feature\Auction;

use App\Actions\Auction\PlaceBidAction;
use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Enums\WinnerMode;
use App\Events\AuctionStateChanged;
use App\Events\BidPlaced;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use App\Services\AuctionSessionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * WebSocket-события и авторизация каналов аукциона (фаза 8.9).
 */
class AuctionBroadcastTest extends TestCase
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
     * @return array{0: Procedure, 1: ProcedureLot, 2: User&Authenticatable}
     */
    private function makeOpenInProgressAuction(): array
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create([
            'email' => 'bidder-secret@test.test',
        ]);
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Open,
            'starts_at' => now()->subHour(),
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
            'current_price' => null,
        ]);

        return [$procedure->fresh(['auctionSetting']), $lot, $participant];
    }

    /**
     * @return void
     */
    public function test_place_bid_broadcasts_ticker_without_pii(): void
    {
        Event::fake([BidPlaced::class]);

        [$procedure, $lot, $participant] = $this->makeOpenInProgressAuction();

        app(PlaceBidAction::class)->execute(
            $procedure,
            $lot,
            $participant,
            '99000.00',
            '127.0.0.1',
        );

        Event::assertDispatched(BidPlaced::class, function (BidPlaced $event) use ($lot): bool {
            $payload = $event->broadcastWith();

            $this->assertArrayNotHasKey('user_id', $payload);
            $this->assertArrayNotHasKey('email', $payload);
            $this->assertSame($lot->id, $payload['lot_id']);
            $this->assertSame('99000.00', $payload['current_price']);
            $this->assertStringNotContainsString(
                'bidder-secret@test.test',
                json_encode($payload, JSON_THROW_ON_ERROR),
            );

            return true;
        });
    }

    /**
     * @return void
     */
    public function test_start_broadcasts_auction_state_changed(): void
    {
        Event::fake([AuctionStateChanged::class]);

        /** @var User&Authenticatable $admin */
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

        app(AuctionSessionService::class)->start($procedure->fresh(['auctionSetting', 'lots']), $admin);

        Event::assertDispatched(AuctionStateChanged::class, function (AuctionStateChanged $event): bool {
            return $event->action === 'start'
                && $event->broadcastWith()['status'] === ProcedureStatus::InProgress->value;
        });
    }

    /**
     * @return void
     */
    public function test_participant_can_auth_private_auction_channel(): void
    {
        [$procedure, , $participant] = $this->makeOpenInProgressAuction();

        $this->actingAs($participant)
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-auction.'.$procedure->id,
            ])
            ->assertOk();
    }

    /**
     * @return void
     */
    public function test_participant_cannot_join_presence_auction_channel(): void
    {
        [$procedure, , $participant] = $this->makeOpenInProgressAuction();

        $this->actingAs($participant)
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'presence-auction.presence.'.$procedure->id,
            ])
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_admin_can_join_presence_auction_channel(): void
    {
        [$procedure] = $this->makeOpenInProgressAuction();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create(['inn' => '7700000000']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'presence-auction.presence.'.$procedure->id,
            ])
            ->assertOk();
    }

    /**
     * @return void
     */
    public function test_guest_cannot_auth_broadcast_channel(): void
    {
        [$procedure] = $this->makeOpenInProgressAuction();

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-auction.'.$procedure->id,
        ])->assertUnauthorized();
    }
}
