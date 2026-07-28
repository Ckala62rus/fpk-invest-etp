<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты настроек оповещений (фаза 3.5).
 */
class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_read_notification_settings(): void
    {
        $this->getJson('/api/notification-settings')->assertUnauthorized();
    }

    public function test_participant_can_read_and_update_settings(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        UserNotificationSetting::query()->create([
            'user_id' => $user->id,
            'all_disabled' => false,
            'notify_new_auctions' => true,
            'notify_new_procedures' => true,
            'notify_day_before' => true,
            'notify_hour_before' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.notify_new_auctions', true);

        $this->actingAs($user)
            ->putJson('/api/notification-settings', [
                'all_disabled' => true,
                'notify_new_auctions' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.all_disabled', true)
            ->assertJsonPath('data.notify_new_auctions', false);

        $this->assertDatabaseHas('user_notification_settings', [
            'user_id' => $user->id,
            'all_disabled' => true,
            'notify_new_auctions' => false,
        ]);
    }

    public function test_settings_are_created_if_missing(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.notify_new_procedures', true);

        $this->assertDatabaseHas('user_notification_settings', [
            'user_id' => $user->id,
        ]);
    }
}
