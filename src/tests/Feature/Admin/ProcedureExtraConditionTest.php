<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomFieldType;
use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\ProcedureExtraConditionTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты доп. условий аукциона (фаза 5.6).
 */
class ProcedureExtraConditionTest extends TestCase
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
     * super_admin создаёт шаблон условия.
     *
     * @return void
     */
    public function test_super_admin_can_create_template(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/extra-condition-templates', [
                'name' => 'Отсрочка платежа',
                'field_type' => CustomFieldType::Text->value,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Отсрочка платежа');
    }

    /**
     * trade_admin синхронизирует значения условий у черновика.
     *
     * @return void
     */
    public function test_trade_admin_can_sync_condition_values(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $template = ProcedureExtraConditionTemplate::query()->create([
            'name' => 'Доставка',
            'field_type' => CustomFieldType::Text,
            'is_active' => true,
        ]);

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'status' => ProcedureStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/extra-conditions', [
                'conditions' => [
                    ['template_id' => $template->id, 'value' => 'Самовывоз'],
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.value', 'Самовывоз');

        $this->assertDatabaseHas('procedure_extra_condition_values', [
            'procedure_id' => $procedure->id,
            'template_id' => $template->id,
            'value' => 'Самовывоз',
        ]);
    }

    /**
     * У опубликованной процедуры условия менять нельзя.
     *
     * @return void
     */
    public function test_cannot_sync_conditions_on_published(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $template = ProcedureExtraConditionTemplate::query()->create([
            'name' => 'Условие',
            'field_type' => CustomFieldType::Text,
            'is_active' => true,
        ]);

        $procedure = Procedure::factory()->published()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/extra-conditions', [
                'conditions' => [
                    ['template_id' => $template->id, 'value' => 'x'],
                ],
            ])
            ->assertStatus(422);
    }
}
