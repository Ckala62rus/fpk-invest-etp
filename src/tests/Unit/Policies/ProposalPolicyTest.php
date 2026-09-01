<?php

namespace Tests\Unit\Policies;

use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use App\Policies\ProposalPolicy;
use App\Services\ProposalVisibilityService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-тесты ProposalPolicy (фаза 6.5).
 */
class ProposalPolicyTest extends TestCase
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
     * @return void
     */
    public function test_participant_can_view_own_proposal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create(['user_id' => $user->id]);

        $policy = new ProposalPolicy(new ProposalVisibilityService());

        $this->assertTrue($policy->view($user, $proposal));
    }

    /**
     * @return void
     */
    public function test_auditor_can_view_any_in_procedure(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $proposal = Proposal::factory()->submitted()->create();

        $policy = new ProposalPolicy(new ProposalVisibilityService());

        $this->assertTrue($policy->view($auditor, $proposal));
        $this->assertTrue($policy->viewAny($auditor));
    }
}
