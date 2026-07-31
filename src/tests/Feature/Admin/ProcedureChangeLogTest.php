<?php

namespace Tests\Feature\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты журнала изменений ТЗП (фаза 5.9).
 */
class ProcedureChangeLogTest extends TestCase
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
     * Обновление черновика создаёт запись change log.
     *
     * @return void
     */
    public function test_update_creates_change_log(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create([
            'status' => ProcedureStatus::Draft,
            'title' => 'Старое название',
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id, [
                'title' => 'Новое название',
            ])
            ->assertOk();

        $this->assertDatabaseHas('procedure_change_logs', [
            'procedure_id' => $procedure->id,
            'changed_by' => $admin->id,
            'approval_status' => ApprovalStatus::Approved->value,
        ]);

        $log = ProcedureChangeLog::query()->where('procedure_id', $procedure->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('Старое название', $log->diff['title']['old']);
        $this->assertSame('Новое название', $log->diff['title']['new']);
    }

    /**
     * Список change logs доступен админу.
     *
     * @return void
     */
    public function test_can_list_change_logs(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
        ]);

        ProcedureChangeLog::query()->create([
            'procedure_id' => $procedure->id,
            'changed_by' => $admin->id,
            'change_summary' => 'Тест',
            'diff' => ['title' => ['old' => 'a', 'new' => 'b']],
            'approval_status' => ApprovalStatus::Approved,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/change-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.change_summary', 'Тест');
    }
}
