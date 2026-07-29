<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\ProcedureCustomField;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты настраиваемых полей ТЗП (фаза 5.2).
 */
class ProcedureCustomFieldTest extends TestCase
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
     * trade_admin добавляет поле select с options.
     *
     * @return void
     */
    public function test_trade_admin_can_create_select_field(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'status' => ProcedureStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/custom-fields', [
                'scope' => CustomFieldScope::Participant->value,
                'label' => 'Срок поставки',
                'field_type' => CustomFieldType::Select->value,
                'options' => ['7 дней', '14 дней', '30 дней'],
                'is_required' => true,
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Срок поставки')
            ->assertJsonPath('data.field_type', 'select')
            ->assertJsonPath('data.options.0', '7 дней');

        $this->assertDatabaseHas('procedure_custom_fields', [
            'procedure_id' => $procedure->id,
            'label' => 'Срок поставки',
        ]);
    }

    /**
     * Select без options — 422.
     *
     * @return void
     */
    public function test_select_field_requires_options(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/custom-fields', [
                'scope' => CustomFieldScope::Participant->value,
                'label' => 'Выбор',
                'field_type' => CustomFieldType::Select->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['options']);
    }

    /**
     * Список, обновление и удаление поля у черновика.
     *
     * @return void
     */
    public function test_can_list_update_and_delete_field(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create(['status' => ProcedureStatus::Draft]);
        $field = ProcedureCustomField::query()->create([
            'procedure_id' => $procedure->id,
            'scope' => CustomFieldScope::Participant,
            'label' => 'Комментарий',
            'field_type' => CustomFieldType::Text,
            'is_required' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/custom-fields')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$procedure->id.'/custom-fields/'.$field->id, [
                'label' => 'Комментарий участника',
                'is_required' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Комментарий участника')
            ->assertJsonPath('data.is_required', true);

        $this->actingAs($admin)
            ->deleteJson('/api/admin/procedures/'.$procedure->id.'/custom-fields/'.$field->id)
            ->assertOk();

        $this->assertDatabaseMissing('procedure_custom_fields', ['id' => $field->id]);
    }

    /**
     * У опубликованной процедуры поля менять нельзя.
     *
     * @return void
     */
    public function test_cannot_modify_fields_on_published_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->published()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/custom-fields', [
                'scope' => CustomFieldScope::Participant->value,
                'label' => 'Поле',
                'field_type' => CustomFieldType::Text->value,
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Настраиваемые поля можно менять только у черновика процедуры.',
            );
    }
}
