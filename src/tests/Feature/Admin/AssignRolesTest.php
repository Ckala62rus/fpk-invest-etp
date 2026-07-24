<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты назначения ролей (фаза 2.5).
 */
class AssignRolesTest extends TestCase
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
     * Гость не может назначать роли.
     *
     * @return void
     */
    public function test_guest_cannot_assign_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->putJson("/api/admin/users/{$user->id}/roles", [
            'roles' => ['trade_admin'],
        ])->assertUnauthorized();
    }

    /**
     * Администратор торгов не может назначать роли (только super_admin).
     *
     * @return void
     */
    public function test_trade_admin_cannot_assign_roles(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/roles", [
                'roles' => ['auditor'],
            ])
            ->assertForbidden();
    }

    /**
     * Главный администратор назначает роль trade_admin участнику.
     *
     * @return void
     */
    public function test_super_admin_can_assign_roles(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/roles", [
                'roles' => ['trade_admin'],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Роли пользователя обновлены.')
            ->assertJsonPath('data.roles.0', 'trade_admin');

        $this->assertTrue($target->fresh()->hasRole('trade_admin'));
        $this->assertFalse($target->fresh()->hasRole('participant'));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'event' => 'roles_assigned',
            'subject_id' => $target->id,
            'causer_id' => $admin->id,
        ]);
    }

    /**
     * Нельзя менять роли самому себе.
     *
     * @return void
     */
    public function test_super_admin_cannot_change_own_roles(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$admin->id}/roles", [
                'roles' => ['trade_admin'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Нельзя изменять роли собственной учётной записи.');
    }

    /**
     * Можно снять super_admin с другого пользователя (вызывающий остаётся главным администратором).
     *
     * @return void
     */
    public function test_can_demote_another_super_admin(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $second = User::factory()->create();
        $second->assignRole('super_admin');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$second->id}/roles", [
                'roles' => ['auditor'],
            ])
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'auditor');

        $this->assertFalse($second->fresh()->hasRole('super_admin'));
        $this->assertTrue($admin->fresh()->hasRole('super_admin'));
    }

    /**
     * Роль guest недопустима для назначения.
     *
     * @return void
     */
    public function test_guest_role_is_not_assignable(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/roles", [
                'roles' => ['guest'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.0']);
    }

    /**
     * Пустой список ролей — 422.
     *
     * @return void
     */
    public function test_roles_array_must_not_be_empty(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/roles", [
                'roles' => [],
            ])
            ->assertUnprocessable();
    }
}
