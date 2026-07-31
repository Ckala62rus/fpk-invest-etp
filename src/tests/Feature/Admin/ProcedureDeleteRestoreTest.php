<?php

namespace Tests\Feature\Admin;

use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты soft delete / restore ТЗП (фаза 5.8).
 */
class ProcedureDeleteRestoreTest extends TestCase
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
     * super_admin мягко удаляет и восстанавливает процедуру.
     *
     * @return void
     */
    public function test_super_admin_can_soft_delete_and_restore(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create([
            'status' => ProcedureStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/procedures/'.$procedure->id)
            ->assertOk()
            ->assertJsonPath('message', 'Процедура перемещена в удалённые.');

        $this->assertSoftDeleted('procedures', ['id' => $procedure->id]);
        $this->assertSame(
            $admin->id,
            Procedure::withTrashed()->find($procedure->id)?->deleted_by,
        );

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/restore')
            ->assertOk()
            ->assertJsonPath('data.id', $procedure->id)
            ->assertJsonPath('data.deleted_at', null);

        $this->assertNull($procedure->fresh()->deleted_by);
        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * trade_admin не может удалять процедуры.
     *
     * @return void
     */
    public function test_trade_admin_cannot_delete_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/procedures/'.$procedure->id)
            ->assertForbidden();
    }
}
