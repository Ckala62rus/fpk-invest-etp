<?php

namespace Tests\Feature\Admin;

use App\Models\CompanyGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CRUD групп компаний холдинга (фаза 3.1).
 */
class CompanyGroupTest extends TestCase
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
     * Гость не может читать список групп компаний.
     *
     * @return void
     */
    public function test_guest_cannot_list_company_groups(): void
    {
        $this->getJson('/api/admin/company-groups')->assertUnauthorized();
    }

    /**
     * trade_admin получает 403 на CRUD групп компаний.
     *
     * @return void
     */
    public function test_trade_admin_cannot_manage_company_groups(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups')
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/admin/company-groups', ['name' => 'Тестовая группа'])
            ->assertForbidden();
    }

    /**
     * super_admin создаёт группу компаний.
     *
     * @return void
     */
    public function test_super_admin_can_create_company_group(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/company-groups', [
                'name' => 'ФПК Инвест',
                'sort_order' => 10,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Группа компаний создана.')
            ->assertJsonPath('data.name', 'ФПК Инвест')
            ->assertJsonPath('data.sort_order', 10)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('company_groups', [
            'name' => 'ФПК Инвест',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * super_admin читает список с пагинацией.
     *
     * @return void
     */
    public function test_super_admin_can_list_company_groups(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        CompanyGroup::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups')
            ->assertOk()
            ->assertJsonPath('message', 'Список групп компаний.')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'sort_order', 'is_active'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    /**
     * Поиск по названию группы.
     *
     * @return void
     */
    public function test_can_search_company_groups_by_name(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        CompanyGroup::factory()->create(['name' => 'Холдинг Альфа']);
        CompanyGroup::factory()->create(['name' => 'Другая группа']);

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups?search=альфа')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Холдинг Альфа');
    }

    /**
     * super_admin обновляет группу компаний.
     *
     * @return void
     */
    public function test_super_admin_can_update_company_group(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $group = CompanyGroup::factory()->create(['name' => 'Старое имя']);

        $this->actingAs($admin)
            ->putJson("/api/admin/company-groups/{$group->id}", [
                'name' => 'Новое имя',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Новое имя')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('company_groups', [
            'id' => $group->id,
            'name' => 'Новое имя',
            'is_active' => false,
        ]);
    }

    /**
     * super_admin мягко удаляет группу компаний.
     *
     * @return void
     */
    public function test_super_admin_can_soft_delete_company_group(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $group = CompanyGroup::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/company-groups/{$group->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Группа компаний удалена.');

        $this->assertSoftDeleted('company_groups', ['id' => $group->id]);
    }

    /**
     * Удалённая группа скрыта по умолчанию и видна при trashed=with.
     *
     * @return void
     */
    public function test_trashed_filter_includes_soft_deleted_groups(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $group = CompanyGroup::factory()->create(['name' => 'Удаляемая']);
        $group->delete();

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups?search=Удаляемая')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups?trashed=with&search=Удаляемая')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * Создание без name — 422.
     *
     * @return void
     */
    public function test_store_requires_name(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/company-groups', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Название группы компаний обязательно для заполнения.');
    }

    /**
     * Просмотр несуществующей группы — 404.
     *
     * @return void
     */
    public function test_show_missing_company_group_returns_404(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/company-groups/999999')
            ->assertNotFound();
    }
}
