<?php

namespace Tests\Feature\Admin;

use App\Models\Procedure;
use App\Models\ProcedureCustomField;
use App\Models\Proposal;
use App\Models\ProposalAccessLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use App\Enums\EntityType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты видимости КП для админа (фаза 6.5).
 */
class ProposalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * До дедлайна админ уже видит полное КП (нужно для рассмотрения и допуска).
     *
     * @return void
     */
    public function test_admin_sees_full_proposal_before_deadline(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'responsible_user_id' => $admin->id,
            'ends_at' => now()->addDays(3),
        ]);

        /** @var User $participant */
        $participant = User::factory()->create();
        UserProfile::query()->create([
            'user_id' => $participant->id,
            'entity_type' => EntityType::Legal,
            'name' => 'ООО Ромашка',
            'phone' => '+79001234567',
            'director_name' => 'Иванов И.И.',
            'contact_persons' => 'Петров',
            'pd_consent_at' => now(),
        ]);

        $field = ProcedureCustomField::query()->create([
            'procedure_id' => $procedure->id,
            'scope' => CustomFieldScope::Participant,
            'label' => 'Цена',
            'field_type' => CustomFieldType::Decimal,
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
        ]);
        $proposal->fieldValues()->create([
            'procedure_custom_field_id' => $field->id,
            'value' => '999999',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id)
            ->assertOk()
            ->assertJsonPath('data.participant_name', 'ООО Ромашка')
            ->assertJsonPath('data.content_hidden', false)
            ->assertJsonPath('data.user_id', $participant->id)
            ->assertJsonPath('data.field_values.0.value', '999999');
    }

    /**
     * После дедлайна админ видит полное КП и пишется access log.
     *
     * @return void
     */
    public function test_admin_sees_full_proposal_after_deadline(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'ends_at' => now()->subHour(),
        ]);

        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/proposals/'.$proposal->id)
            ->assertOk()
            ->assertJsonPath('data.content_hidden', false)
            ->assertJsonPath('data.user_id', $proposal->user_id);

        $this->assertDatabaseHas('proposal_access_logs', [
            'proposal_id' => $proposal->id,
            'user_id' => $admin->id,
            'action' => 'view',
        ]);
    }

    /**
     * Участник видит только свою заявку целиком.
     *
     * @return void
     */
    public function test_participant_sees_own_proposal_full(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        $this->actingAs($participant)
            ->getJson('/api/proposals/'.$proposal->id)
            ->assertOk()
            ->assertJsonPath('data.id', $proposal->id)
            ->assertJsonPath('data.user_id', $participant->id);
    }

    /**
     * Чужую заявку участник не видит.
     *
     * @return void
     */
    public function test_participant_cannot_view_foreign_proposal(): void
    {
        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create();

        $this->actingAs($other)
            ->getJson('/api/proposals/'.$proposal->id)
            ->assertForbidden();
    }

    /**
     * Список заявок до дедлайна отдаёт полное содержимое (для рассмотрения).
     *
     * @return void
     */
    public function test_proposal_list_shows_full_content_before_deadline(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'ends_at' => now()->addDays(2),
        ]);

        Proposal::factory()->submitted()->create(['procedure_id' => $procedure->id]);

        $this->actingAs($admin)
            ->getJson('/api/admin/procedures/'.$procedure->id.'/proposals')
            ->assertOk()
            ->assertJsonPath('data.0.content_hidden', false);
    }
}
