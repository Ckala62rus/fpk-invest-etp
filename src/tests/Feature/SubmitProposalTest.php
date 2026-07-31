<?php

namespace Tests\Feature;

use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\ProposalStatus;
use App\Models\Procedure;
use App\Models\ProcedureCustomField;
use App\Models\ProcedureParticipant;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты подачи коммерческого предложения (фаза 6.1).
 */
class SubmitProposalTest extends TestCase
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
     * Успешная подача КП до дедлайна с обязательными полями.
     *
     * @return void
     */
    public function test_participant_can_submit_proposal_before_deadline(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'visibility' => ProcedureVisibility::Open,
            'ends_at' => now()->addDays(3),
        ]);

        $priceField = ProcedureCustomField::query()->create([
            'procedure_id' => $procedure->id,
            'scope' => CustomFieldScope::Participant,
            'label' => 'Цена',
            'field_type' => CustomFieldType::Decimal,
            'options' => null,
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
                'field_values' => [
                    [
                        'procedure_custom_field_id' => $priceField->id,
                        'value' => '125000.50',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Коммерческое предложение подано.')
            ->assertJsonPath('data.status', ProposalStatus::Submitted->value)
            ->assertJsonPath('data.field_values.0.value', '125000.50');

        $this->assertDatabaseHas('proposals', [
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ProposalStatus::Submitted->value,
        ]);

        $this->assertDatabaseHas('proposal_field_values', [
            'procedure_custom_field_id' => $priceField->id,
            'value' => '125000.50',
        ]);
    }

    /**
     * После ends_at подать КП нельзя.
     *
     * @return void
     */
    public function test_cannot_submit_after_deadline(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Срок приёма коммерческих предложений истёк.');
    }

    /**
     * Повторная подача одним участником запрещена.
     *
     * @return void
     */
    public function test_cannot_submit_twice(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'ends_at' => now()->addDays(2),
        ]);

        Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Вы уже подали коммерческое предложение по этой процедуре.');
    }

    /**
     * Закрытая процедура: без приглашения — 403.
     *
     * @return void
     */
    public function test_closed_procedure_requires_invitation(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'visibility' => ProcedureVisibility::Closed,
            'ends_at' => now()->addDays(5),
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'В закрытую процедуру могут подавать заявки только приглашённые участники.',
            );
    }

    /**
     * Закрытая процедура: приглашённый участник может подать КП.
     *
     * @return void
     */
    public function test_invited_participant_can_submit_to_closed_procedure(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'visibility' => ProcedureVisibility::Closed,
            'status' => ProcedureStatus::Accepting,
            'ends_at' => now()->addDays(5),
        ]);

        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertCreated();
    }

    /**
     * Обязательное поле участника без значения — 422.
     *
     * @return void
     */
    public function test_required_participant_field_must_be_filled(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'type' => ProcedureType::RequestForProposal,
            'ends_at' => now()->addDay(),
        ]);

        ProcedureCustomField::query()->create([
            'procedure_id' => $procedure->id,
            'scope' => CustomFieldScope::Participant,
            'label' => 'Срок поставки',
            'field_type' => CustomFieldType::Text,
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
                'field_values' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Проверьте заполнение полей коммерческого предложения.');
    }

    /**
     * Гость без auth — 401.
     *
     * @return void
     */
    public function test_guest_cannot_submit(): void
    {
        $procedure = Procedure::factory()->accepting()->create();

        $this->postJson('/api/procedures/'.$procedure->id.'/proposals', [
            'contract_form_agreed' => true,
        ])->assertUnauthorized();
    }

    /**
     * Админ торгов без роли participant — 403.
     *
     * @return void
     */
    public function test_trade_admin_cannot_submit(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create();

        $this->actingAs($admin)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertForbidden();
    }

    /**
     * Без согласия с формой договора — 422.
     *
     * @return void
     */
    public function test_contract_form_agreement_required(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => false,
            ])
            ->assertStatus(422);
    }

    /**
     * На аукцион КП не подаётся.
     *
     * @return void
     */
    public function test_cannot_submit_to_auction(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $procedure = Procedure::factory()->auction()->create([
            'ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($participant)
            ->postJson('/api/procedures/'.$procedure->id.'/proposals', [
                'contract_form_agreed' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Коммерческое предложение подаётся только на запрос предложений.');
    }
}
