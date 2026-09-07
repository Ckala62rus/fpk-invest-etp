<?php

namespace Tests\Feature\Auction;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\EntityType;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Enums\WinnerMode;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security-тесты изоляции ставок и ПДн участников (фаза 8.7).
 */
class AuctionBidIsolationTest extends TestCase
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
     * @return array{0: Procedure, 1: ProcedureLot, 2: User&Authenticatable, 3: User&Authenticatable}
     */
    private function makeAuctionWithTwoBidders(): array
    {
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
            'winner_user_id' => null,
            'start_price' => '100000.00',
            'current_price' => '97000.00',
        ]);

        /** @var User&Authenticatable $alice */
        $alice = User::factory()->create([
            'email' => 'alice-secret@test.test',
            'inn' => '1111111111',
        ]);
        $alice->assignRole('participant');
        UserProfile::query()->create([
            'user_id' => $alice->id,
            'entity_type' => EntityType::Legal,
            'name' => 'ООО АлисаСекрет',
            'phone' => '+79001111111',
            'director_name' => 'Алисина ФИО',
            'contact_persons' => 'Секретный контакт Алисы',
            'pd_consent_at' => now(),
        ]);

        /** @var User&Authenticatable $bob */
        $bob = User::factory()->create([
            'email' => 'bob-secret@test.test',
            'inn' => '2222222222',
        ]);
        $bob->assignRole('participant');
        UserProfile::query()->create([
            'user_id' => $bob->id,
            'entity_type' => EntityType::Legal,
            'name' => 'ООО БобСекрет',
            'phone' => '+79002222222',
            'director_name' => 'Бобов ФИО',
            'contact_persons' => 'Секретный контакт Боба',
            'pd_consent_at' => now(),
        ]);

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'user_id' => $alice->id,
            'amount' => '98000.00',
            'ip_address' => '10.10.10.10',
        ]);

        AuctionBid::factory()->create([
            'procedure_id' => $procedure->id,
            'lot_id' => $lot->id,
            'user_id' => $bob->id,
            'amount' => '97000.00',
            'ip_address' => '10.20.20.20',
        ]);

        $lot->update(['winner_user_id' => $bob->id]);

        return [$procedure, $lot->fresh(), $alice, $bob];
    }

    /**
     * @param mixed $payload JSON ответа
     * @param list<string> $secrets Строки, которых не должно быть
     * @return void
     */
    private function assertJsonMissingSecrets(mixed $payload, array $secrets): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->assertNotFalse($encoded);

        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString(
                $secret,
                $encoded,
                'В ответе участника не должно быть: '.$secret,
            );
        }
    }

    /**
     * @return void
     */
    public function test_participant_sees_only_own_bids_without_foreign_pii(): void
    {
        [$procedure, $lot, $alice, $bob] = $this->makeAuctionWithTwoBidders();

        $response = $this->actingAs($alice)
            ->getJson('/api/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '98000.00')
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.ip_address')
            ->assertJsonMissingPath('data.0.user');

        $this->assertJsonMissingSecrets($response->json(), [
            'bob-secret@test.test',
            '+79002222222',
            '2222222222',
            'ООО БобСекрет',
            'Бобов ФИО',
            '10.20.20.20',
        ]);
    }

    /**
     * @return void
     */
    public function test_participant_lots_hide_winner_and_do_not_embed_bids(): void
    {
        [$procedure, $lot, $alice, $bob] = $this->makeAuctionWithTwoBidders();

        $response = $this->actingAs($alice)
            ->getJson('/api/procedures/'.$procedure->id.'/lots')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.current_price', '97000.00')
            ->assertJsonMissingPath('data.0.winner_user_id')
            ->assertJsonMissingPath('data.0.bids');

        $this->assertJsonMissingSecrets($response->json(), [
            'bob-secret@test.test',
            '+79002222222',
            'ООО БобСекрет',
        ]);

        $this->assertNotSame($lot->winner_user_id, $alice->id);
    }

    /**
     * @return void
     */
    public function test_participant_cannot_access_admin_bid_list(): void
    {
        [$procedure, $lot, $alice] = $this->makeAuctionWithTwoBidders();

        $this->actingAs($alice)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_admin_sees_all_bids_with_contacts(): void
    {
        [$procedure, $lot] = $this->makeAuctionWithTwoBidders();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/lots/'.$lot->id.'/bids')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['email' => 'bob-secret@test.test'])
            ->assertJsonFragment(['phone' => '+79002222222'])
            ->assertJsonFragment(['organization_name' => 'ООО БобСекрет']);
    }

    /**
     * @return void
     */
    public function test_public_procedure_card_has_no_bids_or_customer_contacts(): void
    {
        [$procedure] = $this->makeAuctionWithTwoBidders();
        $procedure->update([
            'visibility' => ProcedureVisibility::Open,
            'customer_contact_name' => 'Секретный заказчик',
            'customer_contact_email' => 'customer-secret@test.test',
        ]);

        $json = $this->getJson('/api/procedures/'.$procedure->id)
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('customer_contact_name', $json['data']);
        $this->assertArrayNotHasKey('customer_contact_email', $json['data']);
        $this->assertArrayNotHasKey('bids', $json['data']);
        $this->assertArrayNotHasKey('participants', $json['data']);

        $this->assertJsonMissingSecrets($json, [
            'bob-secret@test.test',
            'alice-secret@test.test',
            'customer-secret@test.test',
            'Секретный заказчик',
        ]);
    }
}
