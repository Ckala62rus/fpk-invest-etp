<?php

namespace Tests\Feature\Admin;

use App\Enums\EmailSendStatus;
use App\Models\EmailSendLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты журнала отправки email (фаза 7.5).
 */
class EmailSendLogTest extends TestCase
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
    public function test_super_admin_can_list_email_send_logs(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $template = NotificationTemplate::factory()->create();

        EmailSendLog::query()->create([
            'template_id' => $template->id,
            'recipient_email' => 'test@example.com',
            'subject' => 'Тест',
            'status' => EmailSendStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/email-send-logs')
            ->assertOk()
            ->assertJsonPath('data.0.recipient_email', 'test@example.com')
            ->assertJsonPath('data.0.template_code', $template->code);
    }

    /**
     * @return void
     */
    public function test_trade_admin_cannot_list_email_send_logs(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/email-send-logs')
            ->assertForbidden();
    }
}
