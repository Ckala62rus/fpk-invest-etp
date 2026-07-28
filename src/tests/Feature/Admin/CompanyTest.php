<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\CompanyGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CRUD предприятий-заказчиков (фаза 3.3).
 */
class CompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_participant_cannot_manage_companies(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->getJson('/api/admin/companies')
            ->assertForbidden();
    }

    public function test_super_admin_can_crud_company(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $group = CompanyGroup::factory()->create();

        $create = $this->actingAs($admin)
            ->postJson('/api/admin/companies', [
                'company_group_id' => $group->id,
                'name' => 'ООО Заказчик',
                'inn' => '7700112233',
                'is_external' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.inn', '7700112233');

        $id = $create->json('data.id');

        $this->actingAs($admin)
            ->getJson("/api/admin/companies/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'ООО Заказчик');

        $this->actingAs($admin)
            ->putJson("/api/admin/companies/{$id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/companies/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('companies', ['id' => $id]);
    }

    public function test_can_search_companies_by_inn(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Company::factory()->create(['inn' => '5511223344', 'name' => 'А']);
        Company::factory()->create(['inn' => '9988776655', 'name' => 'Б']);

        $this->actingAs($admin)
            ->getJson('/api/admin/companies?search=551122')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.inn', '5511223344');
    }
}
