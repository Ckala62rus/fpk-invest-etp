<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты списка пользователей админки (фаза 2.3).
 */
class UserListTest extends TestCase
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
     * Гость получает 401 без сессии Sanctum.
     *
     * @return void
     */
    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    /**
     * Участник без права users.view получает 403.
     *
     * @return void
     */
    public function test_participant_cannot_list_users(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    /**
     * Аудитор может читать список пользователей.
     *
     * @return void
     */
    public function test_auditor_can_list_users(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        User::factory()->count(2)->create()->each(
            static fn (User $user) => $user->assignRole('participant'),
        );

        $this->actingAs($auditor)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Список пользователей.')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'inn', 'email', 'status', 'roles', 'blocked_until', 'block_reason'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /**
     * Фильтр status возвращает только пользователей с указанным статусом.
     *
     * @return void
     */
    public function test_can_filter_users_by_status(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $pending = User::factory()->pendingApproval()->create();
        $pending->assignRole('participant');

        $active = User::factory()->create();
        $active->assignRole('participant');

        $this->actingAs($admin)
            ->getJson('/api/admin/users?status='.UserStatus::PendingApproval->value)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $pending->id);
    }

    /**
     * Поиск по ИНН (идентификационный номер налогоплательщика) находит пользователя.
     *
     * @return void
     */
    public function test_can_search_users_by_inn(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $target = User::factory()->create(['inn' => '7711223344']);
        $target->assignRole('participant');

        User::factory()->create(['inn' => '9900112233'])->assignRole('participant');

        $this->actingAs($admin)
            ->getJson('/api/admin/users?search=771122')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.inn', '7711223344');
    }

    /**
     * Поиск по наименованию из профиля работает через ilike.
     *
     * @return void
     */
    public function test_can_search_users_by_profile_name(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');
        UserProfile::query()->create([
            'user_id' => $target->id,
            'entity_type' => 'legal',
            'name' => 'ООО Ромашка Тест',
            'phone' => '+79990001122',
            'director_name' => 'Иванов И.И.',
            'director_birth_date' => '1980-01-01',
            'contact_persons' => 'Петров',
            'pd_consent_at' => now(),
        ]);

        $other = User::factory()->create();
        $other->assignRole('participant');
        UserProfile::query()->create([
            'user_id' => $other->id,
            'entity_type' => 'legal',
            'name' => 'АО Другое',
            'phone' => '+79990003344',
            'director_name' => 'Сидоров С.С.',
            'director_birth_date' => '1985-02-02',
            'contact_persons' => 'Козлов',
            'pd_consent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/users?search=ромашка')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $target->id);
    }

    /**
     * Фильтр role оставляет только пользователей с указанной ролью.
     *
     * @return void
     */
    public function test_can_filter_users_by_role(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $tradeAdmin = User::factory()->create();
        $tradeAdmin->assignRole('trade_admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users?role=participant')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($participant->id, $ids);
        $this->assertNotContains($tradeAdmin->id, $ids);
    }

    /**
     * Soft-deleted пользователь скрыт по умолчанию и виден при trashed=with.
     *
     * @return void
     */
    public function test_trashed_filter_includes_soft_deleted_users(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $deleted = User::factory()->create();
        $deleted->assignRole('participant');
        $deleted->delete();

        $this->actingAs($admin)
            ->getJson('/api/admin/users?search='.$deleted->inn)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($admin)
            ->getJson('/api/admin/users?trashed=with&search='.$deleted->inn)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $deleted->id);
    }

    /**
     * Некорректный status даёт 422 с русским сообщением.
     *
     * @return void
     */
    public function test_invalid_status_returns_validation_error(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/users?status=unknown')
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /**
     * per_page ограничивает размер страницы.
     *
     * @return void
     */
    public function test_per_page_limits_page_size(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        User::factory()->count(5)->create()->each(
            static fn (User $user) => $user->assignRole('participant'),
        );

        $this->actingAs($admin)
            ->getJson('/api/admin/users?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }
}
