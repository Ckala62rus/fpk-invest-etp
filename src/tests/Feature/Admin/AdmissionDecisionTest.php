<?php

namespace Tests\Feature\Admin;

use App\Enums\AdmissionDecision as AdmissionDecisionEnum;
use App\Enums\ProposalStatus;
use App\Models\AdmissionDecision;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты решения о допуске КП (фаза 6.3).
 */
class AdmissionDecisionTest extends TestCase
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
     * trade_admin допускает заявку своей процедуры с причиной.
     *
     * @return void
     */
    public function test_trade_admin_can_admit_proposal_with_reason(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'responsible_user_id' => $admin->id,
        ]);

        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($admin)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Admit->value,
                    'reason' => 'Документы соответствуют требованиям.',
                ],
            )
            ->assertCreated()
            ->assertJsonPath('message', 'Заявка допущена.')
            ->assertJsonPath('data.decision', AdmissionDecisionEnum::Admit->value)
            ->assertJsonPath('data.proposal_status', ProposalStatus::Admitted->value);

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => ProposalStatus::Admitted->value,
        ]);

        $this->assertDatabaseHas('admission_decisions', [
            'proposal_id' => $proposal->id,
            'decision' => AdmissionDecisionEnum::Admit->value,
            'decided_by' => $admin->id,
        ]);
    }

    /**
     * Недопуск без причины — 422.
     *
     * @return void
     */
    public function test_reject_requires_reason(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create();
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($admin)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Reject->value,
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /**
     * Недопуск с причиной обновляет статус заявки.
     *
     * @return void
     */
    public function test_can_reject_proposal_with_reason(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create();
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($admin)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Reject->value,
                    'reason' => 'Неполный комплект документов.',
                ],
            )
            ->assertCreated()
            ->assertJsonPath('message', 'Заявка отклонена.')
            ->assertJsonPath('data.decision', AdmissionDecisionEnum::Reject->value);

        $this->assertSame(ProposalStatus::Rejected, $proposal->fresh()->status);
    }

    /**
     * Повторное решение запрещено.
     *
     * @return void
     */
    public function test_cannot_decide_twice(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create();
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        AdmissionDecision::query()->create([
            'proposal_id' => $proposal->id,
            'decision' => AdmissionDecisionEnum::Admit,
            'reason' => 'Уже решено',
            'decided_by' => $admin->id,
            'decided_at' => now(),
        ]);

        // Статус ещё submitted — ловим уникальность решения, а не смену статуса
        $this->actingAs($admin)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Reject->value,
                    'reason' => 'Попытка изменить решение.',
                ],
            )
            ->assertStatus(422)
            ->assertJsonPath('message', 'По этой заявке решение о допуске уже принято.');
    }

    /**
     * trade_admin чужой процедуры — 403.
     *
     * @return void
     */
    public function test_trade_admin_cannot_decide_foreign_procedure(): void
    {
        /** @var User&Authenticatable $owner */
        $owner = User::factory()->create();
        $owner->assignRole('trade_admin');

        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'responsible_user_id' => $owner->id,
        ]);
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($other)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Admit->value,
                    'reason' => 'Чужая процедура.',
                ],
            )
            ->assertForbidden();
    }

    /**
     * Аудитор не принимает решения.
     *
     * @return void
     */
    public function test_auditor_cannot_decide(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $procedure = Procedure::factory()->accepting()->create();
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($auditor)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/admission-decision',
                [
                    'decision' => AdmissionDecisionEnum::Admit->value,
                    'reason' => 'Попытка аудитора.',
                ],
            )
            ->assertForbidden();
    }
}
