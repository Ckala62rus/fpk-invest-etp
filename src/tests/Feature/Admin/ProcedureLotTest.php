<?php

namespace Tests\Feature\Admin;

use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты лотов ТЗП (фаза 5.3).
 */
class ProcedureLotTest extends TestCase
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
     * trade_admin создаёт лот у своего черновика.
     *
     * @return void
     */
    public function test_trade_admin_can_create_lot(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->auction()->create([
            'responsible_user_id' => $admin->id,
            'status' => ProcedureStatus::Draft,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/lots', [
                'name' => 'Лот 1 — бетон',
                'unit' => 'м3',
                'quantity' => 100,
                'start_price' => 150000.50,
                'bid_step' => 1000,
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Лот 1 — бетон')
            ->assertJsonPath('data.start_price', '150000.50')
            ->assertJsonPath('data.current_price', '150000.50');

        $this->assertDatabaseHas('procedure_lots', [
            'procedure_id' => $procedure->id,
            'name' => 'Лот 1 — бетон',
        ]);
    }

    /**
     * Список, обновление и удаление лота.
     *
     * @return void
     */
    public function test_can_list_update_and_delete_lot(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create(['status' => ProcedureStatus::Draft]);
        $lot = ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'name' => 'Старый лот',
            'start_price' => 1000,
            'current_price' => 1000,
            'bid_step' => 100,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/lots')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/lots/'.$lot->id, [
                'name' => 'Новый лот',
                'start_price' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Новый лот')
            ->assertJsonPath('data.start_price', '2000.00')
            ->assertJsonPath('data.current_price', '2000.00');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/procedures/'.$procedure->id.'/lots/'.$lot->id)
            ->assertOk();

        $this->assertDatabaseMissing('procedure_lots', ['id' => $lot->id]);
    }

    /**
     * У опубликованной процедуры лоты менять нельзя.
     *
     * @return void
     */
    public function test_cannot_modify_lots_on_published_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->published()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/lots', [
                'name' => 'Лот',
                'start_price' => 100,
                'bid_step' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Лоты можно менять только у черновика процедуры.');
    }
}
