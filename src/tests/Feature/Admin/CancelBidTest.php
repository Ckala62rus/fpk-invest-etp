<?php

namespace Tests\Feature\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\WinnerMode;
use App\Events\BidCancelled;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Feature-тесты отмены ставки администратором (фаза 8.6).
 */
class CancelBidTest extends TestCase
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
     * @return array{0: Procedure, 1: ProcedureLot, 2: AuctionBid, 3: User&Authenticatable}
     */
    private function makeAuctionWithBid(): array
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
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

        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'start_price' => '100000.00',
            'bid_step' => '1000.00',
            'current_price' => '98000.00',
        ]);

        $bid = AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'amount' => '98000.00',
        ]);

        return [$procedure, $lot, $bid, $admin];
    }

    /**
     * @return void
     */
    public function test_trade_admin_can_cancel_bid_and_reset_price(): void
    {
        Event::fake([BidCancelled::class]);

        [$procedure, $lot, $bid, $admin] = $this->makeAuctionWithBid();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/bids/'.$bid->id.'/cancel', [
                'reason' => 'Ошибка участника при вводе суммы',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_cancelled', true)
            ->assertJsonPath('message', 'Ставка отменена.');

        $this->assertTrue($bid->fresh()->is_cancelled);
        $this->assertSame('100000.00', $lot->fresh()->current_price);

        Event::assertDispatched(BidCancelled::class);
    }

    /**
     * @return void
     */
    public function test_reason_is_required(): void
    {
        [$procedure, , $bid, $admin] = $this->makeAuctionWithBid();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/bids/'.$bid->id.'/cancel', [])
            ->assertUnprocessable();
    }

    /**
     * @return void
     */
    public function test_cannot_cancel_twice(): void
    {
        [$procedure, , $bid, $admin] = $this->makeAuctionWithBid();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/bids/'.$bid->id.'/cancel', [
                'reason' => 'Первая отмена ставки',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/bids/'.$bid->id.'/cancel', [
                'reason' => 'Повторная отмена ставки',
            ])
            ->assertStatus(422);
    }
}
