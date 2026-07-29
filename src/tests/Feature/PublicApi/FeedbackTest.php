<?php

namespace Tests\Feature\PublicApi;

use App\Enums\ComplaintStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты жалоб и сообщений о коррупции (фаза 4.5).
 */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Гость подаёт жалобу с именем и email.
     *
     * @return void
     */
    public function test_guest_can_submit_complaint(): void
    {
        $this->postJson('/api/complaints', [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'subject' => 'Нарушение сроков',
            'message' => 'Процедура не была проведена вовремя.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', ComplaintStatus::New->value)
            ->assertJsonPath('data.subject', 'Нарушение сроков');

        $this->assertDatabaseHas('complaints', [
            'email' => 'ivan@example.com',
            'subject' => 'Нарушение сроков',
            'user_id' => null,
        ]);
    }

    /**
     * Гость без имени/email получает 422.
     *
     * @return void
     */
    public function test_guest_complaint_requires_name_and_email(): void
    {
        $this->postJson('/api/complaints', [
            'subject' => 'Тема',
            'message' => 'Текст',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /**
     * Авторизованный участник может подать жалобу без name/email.
     *
     * @return void
     */
    public function test_authenticated_user_can_submit_complaint_without_contacts(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['email' => 'user@etp.local']);
        $user->assignRole('participant');

        $this->actingAs($user)
            ->postJson('/api/complaints', [
                'subject' => 'Жалоба участника',
                'message' => 'Описание проблемы.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.email', 'user@etp.local');
    }

    /**
     * Гость подаёт сообщение о коррупции.
     *
     * @return void
     */
    public function test_guest_can_submit_corruption_report(): void
    {
        $this->postJson('/api/corruption-reports', [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'message' => 'Подозрение в конфликте интересов.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.message', 'Подозрение в конфликте интересов.');

        $this->assertDatabaseHas('corruption_reports', [
            'email' => 'petr@example.com',
            'user_id' => null,
        ]);
    }
}
