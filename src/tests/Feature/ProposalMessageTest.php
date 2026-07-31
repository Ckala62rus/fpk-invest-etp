<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\ProposalMessage;
use App\Models\User;
use App\Support\Workdays;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты переписки по уточнению КП (фаза 6.4).
 */
class ProposalMessageTest extends TestCase
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
     * Участник пишет сообщение по своей заявке.
     *
     * @return void
     */
    public function test_owner_can_send_message(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/proposals/'.$proposal->id.'/messages', [
                'message' => 'Прошу уточнить требования к составу КП.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.message', 'Прошу уточнить требования к составу КП.');
    }

    /**
     * Админ запрашивает уточнение со сроком ≥ 2 рабочих дня.
     *
     * @return void
     */
    public function test_admin_can_request_clarification_with_two_workdays(): void
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

        $deadline = Workdays::add(now(), 2)->toDateString();

        $this->actingAs($admin)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/messages',
                [
                    'message' => 'Просим уточнить стоимость и сроки.',
                    'request_clarification' => true,
                    'clarification_deadline' => $deadline,
                ],
            )
            ->assertCreated()
            ->assertJsonPath('message', 'Запрошено уточнение коммерческого предложения.');

        $this->assertSame(ProposalStatus::Clarification, $proposal->fresh()->status);
    }

    /**
     * Срок меньше 2 рабочих дней — 422.
     *
     * @return void
     */
    public function test_clarification_deadline_requires_two_workdays(): void
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
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/messages',
                [
                    'message' => 'Слишком короткий срок.',
                    'request_clarification' => true,
                    'clarification_deadline' => now()->addDay()->toDateString(),
                ],
            )
            ->assertStatus(422)
            ->assertJsonPath('message', 'Срок уточнения должен быть не менее 2 рабочих дней.');
    }

    /**
     * Список сообщений для владельца.
     *
     * @return void
     */
    public function test_owner_can_list_messages(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        ProposalMessage::query()->create([
            'proposal_id' => $proposal->id,
            'sender_id' => $participant->id,
            'message' => 'Первое сообщение',
            'attachments' => null,
        ]);

        $this->actingAs($participant)
            ->getJson('/api/proposals/'.$proposal->id.'/messages')
            ->assertOk()
            ->assertJsonPath('data.0.message', 'Первое сообщение');
    }

    /**
     * Чужую переписку участник не видит.
     *
     * @return void
     */
    public function test_cannot_access_foreign_messages(): void
    {
        /** @var User&Authenticatable $owner */
        $owner = User::factory()->create();
        $owner->assignRole('participant');

        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->getJson('/api/proposals/'.$proposal->id.'/messages')
            ->assertForbidden();
    }

    /**
     * Аудитор читает, но не пишет.
     *
     * @return void
     */
    public function test_auditor_can_read_but_not_write(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $procedure = Procedure::factory()->accepting()->create();
        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($auditor)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/messages')
            ->assertOk();

        $this->actingAs($auditor)
            ->postJson(
                '/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id.'/messages',
                ['message' => 'Нельзя'],
            )
            ->assertForbidden();
    }
}
