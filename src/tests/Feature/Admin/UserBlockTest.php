<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты блокировки и разблокировки пользователей (фаза 2.4).
 */
class UserBlockTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC (role-based access control).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Гость не может блокировать пользователей.
     *
     * @return void
     */
    public function test_guest_cannot_block_user(): void
    {
        $user = User::factory()->create();

        $this->postJson("/api/admin/users/{$user->id}/block", [
            'reason' => 'Нарушение регламента',
        ])->assertUnauthorized();
    }

    /**
     * Участник получает 403 на блокировку.
     *
     * @return void
     */
    public function test_participant_cannot_block_user(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($participant)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Нарушение регламента',
            ])
            ->assertForbidden();
    }

    /**
     * Администратор торгов блокирует участника с причиной и сроком.
     *
     * @return void
     */
    public function test_trade_admin_can_block_user(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $until = now()->addDays(3)->toIso8601String();

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Нарушение регламента участия',
                'blocked_until' => $until,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Пользователь заблокирован.')
            ->assertJsonPath('data.status', UserStatus::Blocked->value)
            ->assertJsonPath('data.block_reason', 'Нарушение регламента участия');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'status' => UserStatus::Blocked->value,
            'block_reason' => 'Нарушение регламента участия',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'event' => 'blocked',
            'subject_id' => $target->id,
            'causer_id' => $admin->id,
        ]);
    }

    /**
     * Разблокировка возвращает статус active и очищает поля блокировки.
     *
     * @return void
     */
    public function test_trade_admin_can_unblock_user(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->blocked()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/unblock")
            ->assertOk()
            ->assertJsonPath('message', 'Пользователь разблокирован.')
            ->assertJsonPath('data.status', UserStatus::Active->value)
            ->assertJsonPath('data.block_reason', null)
            ->assertJsonPath('data.blocked_until', null);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'event' => 'unblocked',
            'subject_id' => $target->id,
            'causer_id' => $admin->id,
        ]);
    }

    /**
     * Нельзя заблокировать самого себя.
     *
     * @return void
     */
    public function test_admin_cannot_block_self(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$admin->id}/block", [
                'reason' => 'Тест самоблокировки',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * trade_admin не может блокировать super_admin.
     *
     * @return void
     */
    public function test_trade_admin_cannot_block_super_admin(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $super = User::factory()->create();
        $super->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$super->id}/block", [
                'reason' => 'Попытка блокировки главного администратора',
            ])
            ->assertForbidden();
    }

    /**
     * Повторная блокировка уже заблокированного — 422.
     *
     * @return void
     */
    public function test_cannot_block_already_blocked_user(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->blocked()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Ещё одна блокировка',
            ])
            ->assertStatus(422);
    }

    /**
     * Без причины — 422 с русским сообщением.
     *
     * @return void
     */
    public function test_block_requires_reason(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.reason.0', 'Укажите причину блокировки.');
    }

    /**
     * Аудитор не может блокировать (только просмотр списка).
     *
     * @return void
     */
    public function test_auditor_cannot_block_user(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($auditor)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Аудитор не должен блокировать',
            ])
            ->assertForbidden();
    }
}
