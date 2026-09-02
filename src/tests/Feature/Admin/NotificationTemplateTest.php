<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationEventType;
use App\Models\NotificationTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CRUD шаблонов уведомлений (фаза 7.1).
 */
class NotificationTemplateTest extends TestCase
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
     * @return void
     */
    public function test_guest_cannot_list_notification_templates(): void
    {
        $this->getJson('/api/admin/notification-templates')->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function test_trade_admin_cannot_manage_notification_templates(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/notification-templates')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_super_admin_can_create_and_update_template(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/notification-templates', [
                'code' => 'test_template',
                'name' => 'Тестовый шаблон',
                'subject' => 'Тема {{procedure.number}}',
                'body_html' => '<p>Тело</p>',
                'event_type' => NotificationEventType::Event->value,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'test_template');

        $template = NotificationTemplate::query()->where('code', 'test_template')->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/admin/notification-templates/'.$template->id, [
                'name' => 'Обновлённый шаблон',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Обновлённый шаблон');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/notification-templates/'.$template->id)
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
