<?php

namespace Tests\Feature\Admin;

use App\Models\ClassifierCategory;
use App\Models\CompanyGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CRUD категорий классификатора (фаза 3.2).
 */
class ClassifierCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_trade_admin_cannot_manage_categories(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/classifier-categories')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_and_filter_category(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $group = CompanyGroup::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/classifier-categories', [
                'company_group_id' => $group->id,
                'name' => 'СМР (строительно-монтажные работы)',
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'СМР (строительно-монтажные работы)')
            ->assertJsonPath('data.company_group_id', $group->id);

        ClassifierCategory::factory()->create([
            'company_group_id' => CompanyGroup::factory(),
            'name' => 'Другая',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/classifier-categories?company_group_id='.$group->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_super_admin_can_update_and_delete_category(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $category = ClassifierCategory::factory()->create(['name' => 'Старое']);

        $this->actingAs($admin)
            ->putJson("/api/admin/classifier-categories/{$category->id}", ['name' => 'ИТ (информационные технологии)'])
            ->assertOk()
            ->assertJsonPath('data.name', 'ИТ (информационные технологии)');

        $this->actingAs($admin)
            ->deleteJson("/api/admin/classifier-categories/{$category->id}")
            ->assertOk();

        $this->assertSoftDeleted('classifier_categories', ['id' => $category->id]);
    }

    public function test_store_requires_company_group(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/classifier-categories', ['name' => 'Без группы'])
            ->assertUnprocessable();
    }
}
