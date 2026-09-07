<?php

namespace Tests\Feature\Auction;

use App\Enums\EntityType;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Models\AuctionSession;
use App\Models\Procedure;
use App\Models\ProcedureParticipant;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты присутствия на аукционе (фаза 8.8).
 */
class AuctionPresenceTest extends TestCase
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
     * @return Procedure
     */
    private function makeOpenAuction(): Procedure
    {
        return Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Open,
        ]);
    }

    /**
     * @return void
     */
    public function test_participant_heartbeat_does_not_leak_others(): void
    {
        $procedure = $this->makeOpenAuction();

        /** @var User&Authenticatable $alice */
        $alice = User::factory()->create(['email' => 'alice-online@test.test']);
        $alice->assignRole('participant');

        /** @var User&Authenticatable $bob */
        $bob = User::factory()->create(['email' => 'bob-online@test.test']);
        $bob->assignRole('participant');

        $this->actingAs($bob)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/heartbeat')
            ->assertOk();

        $json = $this->actingAs($alice)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/heartbeat')
            ->assertOk()
            ->assertJsonPath('data.is_online', true)
            ->assertJsonMissingPath('data.online')
            ->json();

        $encoded = json_encode($json, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('bob-online@test.test', (string) $encoded);
        $this->assertArrayNotHasKey('online_count', $json['data']);
    }

    /**
     * @return void
     */
    public function test_admin_sees_online_and_invited_never_visited(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Closed,
            'responsible_user_id' => $admin->id,
        ]);

        /** @var User&Authenticatable $onlineUser */
        $onlineUser = User::factory()->create(['email' => 'online@test.test', 'inn' => '3333333333']);
        $onlineUser->assignRole('participant');
        UserProfile::query()->create([
            'user_id' => $onlineUser->id,
            'entity_type' => EntityType::Legal,
            'name' => 'ООО Онлайн',
            'phone' => '+79003333333',
            'director_name' => 'Директор Онлайн',
            'contact_persons' => 'Контакт',
            'pd_consent_at' => now(),
        ]);

        $absent = User::factory()->create(['email' => 'absent@test.test', 'inn' => '4444444444']);
        $absent->assignRole('participant');

        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $onlineUser->id,
            'status' => ParticipantStatus::Invited,
        ]);
        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $absent->id,
            'status' => ParticipantStatus::Invited,
        ]);

        $this->actingAs($onlineUser)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/heartbeat')
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/auction/presence')
            ->assertOk()
            ->assertJsonPath('data.online_count', 1)
            ->assertJsonPath('data.online.0.email', 'online@test.test')
            ->assertJsonPath('data.online.0.phone', '+79003333333')
            ->assertJsonPath('data.invited_never_visited.0.email', 'absent@test.test');
    }

    /**
     * @return void
     */
    public function test_participant_cannot_read_admin_presence(): void
    {
        $procedure = $this->makeOpenAuction();

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/auction/presence')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_stale_session_is_marked_offline(): void
    {
        $procedure = $this->makeOpenAuction();
        $user = User::factory()->create();
        $user->assignRole('participant');

        AuctionSession::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $user->id,
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(5),
            'is_online' => true,
        ]);

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/auction/presence')
            ->assertOk()
            ->assertJsonPath('data.online_count', 0);

        $this->assertFalse(AuctionSession::query()->firstOrFail()->is_online);
    }

    /**
     * @return void
     */
    public function test_leave_marks_offline(): void
    {
        $procedure = $this->makeOpenAuction();

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/heartbeat')
            ->assertOk();

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/leave')
            ->assertOk()
            ->assertJsonPath('data.is_online', false);

        $this->assertFalse(
            AuctionSession::query()->where('user_id', $participant->id)->firstOrFail()->is_online,
        );
    }

    /**
     * @return void
     */
    public function test_uninvited_cannot_heartbeat_closed_auction(): void
    {
        $procedure = Procedure::factory()->auction()->create([
            'status' => ProcedureStatus::InProgress,
            'visibility' => ProcedureVisibility::Closed,
        ]);

        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/auction/presence/heartbeat')
            ->assertStatus(403);
    }
}
