<?php

namespace Tests\Feature\Admin;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Models\ClassifierCategory;
use App\Models\Company;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты создания и редактирования черновиков ТЗП (фаза 5.1).
 */
class ProcedureDraftTest extends TestCase
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
     * Гость не может создавать процедуры.
     *
     * @return void
     */
    public function test_guest_cannot_create_procedure(): void
    {
        $this->postJson('/api/admin/procedures', [])->assertUnauthorized();
    }

    /**
     * Участник получает 403.
     *
     * @return void
     */
    public function test_participant_cannot_create_procedure(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->postJson('/api/admin/procedures', $this->validPayload())
            ->assertForbidden();
    }

    /**
     * trade_admin создаёт черновик КП (запроса предложений).
     *
     * @return void
     */
    public function test_trade_admin_can_create_rfp_draft(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $payload = $this->validPayload([
            'type' => ProcedureType::RequestForProposal->value,
            'title' => 'Запрос предложений на СМР',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', ProcedureStatus::Draft->value)
            ->assertJsonPath('data.type', ProcedureType::RequestForProposal->value)
            ->assertJsonPath('data.title', 'Запрос предложений на СМР')
            ->assertJsonPath('data.responsible_user_id', $admin->id)
            ->assertJsonPath('data.created_by', $admin->id)
            ->assertJsonPath('data.auction_setting', null);

        $this->assertDatabaseHas('procedures', [
            'title' => 'Запрос предложений на СМР',
            'status' => ProcedureStatus::Draft->value,
            'created_by' => $admin->id,
        ]);
    }

    /**
     * trade_admin создаёт черновик аукциона с настройками по умолчанию.
     *
     * @return void
     */
    public function test_trade_admin_can_create_auction_draft_with_settings(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/procedures', $this->validPayload([
                'type' => ProcedureType::Auction->value,
                'title' => 'Аукцион на поставку',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.type', ProcedureType::Auction->value)
            ->assertJsonPath('data.auction_setting.bid_mode', 'standard')
            ->assertJsonPath('data.auction_setting.auction_mode', 'decrease');

        $procedureId = $response->json('data.id');

        $this->assertDatabaseHas('auction_settings', [
            'procedure_id' => $procedureId,
        ]);

        $this->assertMatchesRegularExpression(
            '/^TZP-\d{8}-\d{4}$/',
            (string) $response->json('data.number'),
        );
    }

    /**
     * Валидация обязательных полей.
     *
     * @return void
     */
    public function test_create_requires_core_fields(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'title', 'company_id', 'classifier_category_id']);
    }

    /**
     * trade_admin видит только свои процедуры в списке.
     *
     * @return void
     */
    public function test_trade_admin_lists_only_own_procedures(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('trade_admin');

        $own = Procedure::factory()->create(['responsible_user_id' => $admin->id]);
        Procedure::factory()->create(['responsible_user_id' => $other->id]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    /**
     * trade_admin не может открыть чужую процедуру.
     *
     * @return void
     */
    public function test_trade_admin_cannot_view_foreign_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $foreign = Procedure::factory()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$foreign->id)
            ->assertForbidden();
    }

    /**
     * Обновление черновика; нельзя править опубликованную.
     *
     * @return void
     */
    public function test_can_update_draft_but_not_published(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $draft = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'status' => ProcedureStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$draft->id, [
                'title' => 'Новое название',
                'visibility' => ProcedureVisibility::Closed->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Новое название')
            ->assertJsonPath('data.visibility', ProcedureVisibility::Closed->value);

        $published = Procedure::factory()->published()->create([
            'responsible_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/procedures/'.$published->id, [
                'title' => 'Нельзя',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Редактировать можно только черновик процедуры.');
    }

    /**
     * auditor может читать список, но не создавать.
     *
     * @return void
     */
    public function test_auditor_can_list_but_not_create(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        Procedure::factory()->create();

        $this->actingAs($auditor)
            ->getJson('/api/admin/procedures')
            ->assertOk();

        $this->actingAs($auditor)
            ->postJson('/api/admin/procedures', $this->validPayload())
            ->assertForbidden();
    }

    /**
     * Собирает валидный payload создания черновика.
     *
     * @param array<string, mixed> $overrides Переопределения полей
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $company = Company::factory()->create();
        $category = ClassifierCategory::factory()->create([
            'company_group_id' => $company->company_group_id,
        ]);

        return array_merge([
            'type' => ProcedureType::RequestForProposal->value,
            'title' => 'Тестовая процедура',
            'company_id' => $company->id,
            'classifier_category_id' => $category->id,
            'description' => 'Описание',
        ], $overrides);
    }
}
