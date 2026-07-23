<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты middleware EnsureUserHasRole (фаза 2.2).
 *
 * Проверяет отказ гостю и участнику, а также доступ ролям супер-админа и админа торгов
 * на маршруте одобрения пользователя.
 */
class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает системные роли RBAC (role-based access control).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Гость без сессии получает 401 на защищённом admin-маршруте.
     *
     * @return void
     */
    public function test_guest_cannot_access_role_protected_route(): void
    {
        $user = User::factory()->pendingApproval()->create();

        $this->postJson("/api/admin/users/{$user->id}/approve")
            ->assertUnauthorized();
    }

    /**
     * Участник с ролью participant получает 403.
     *
     * @return void
     */
    public function test_participant_receives_forbidden(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $target = User::factory()->pendingApproval()->create();

        $this->actingAs($participant)
            ->postJson("/api/admin/users/{$target->id}/approve")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * Аудитор не входит в список ролей маршрута approve → 403.
     *
     * @return void
     */
    public function test_auditor_receives_forbidden(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $target = User::factory()->pendingApproval()->create();

        $this->actingAs($auditor)
            ->postJson("/api/admin/users/{$target->id}/approve")
            ->assertForbidden();
    }

    /**
     * Администратор торгов проходит проверку роли и одобряет пользователя.
     *
     * @return void
     */
    public function test_trade_admin_passes_role_check(): void
    {
        /** @var User&Authenticatable $administrator */
        $administrator = User::factory()->create();
        $administrator->assignRole('trade_admin');

        $target = User::factory()->pendingApproval()->create();

        $this->actingAs($administrator)
            ->postJson("/api/admin/users/{$target->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Active->value);
    }

    /**
     * Главный администратор проходит проверку роли и одобряет пользователя.
     *
     * @return void
     */
    public function test_super_admin_passes_role_check(): void
    {
        /** @var User&Authenticatable $administrator */
        $administrator = User::factory()->create();
        $administrator->assignRole('super_admin');

        $target = User::factory()->pendingApproval()->create();

        $this->actingAs($administrator)
            ->postJson("/api/admin/users/{$target->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Active->value);
    }
}
