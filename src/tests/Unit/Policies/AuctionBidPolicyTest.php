<?php

namespace Tests\Unit\Policies;

use App\Models\AuctionBid;
use App\Models\User;
use App\Policies\AuctionBidPolicy;
use App\Services\AuctionBidVisibilityService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-тесты AuctionBidPolicy (фаза 8.7).
 */
class AuctionBidPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AuctionBidPolicy $policy;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->policy = new AuctionBidPolicy(new AuctionBidVisibilityService);
    }

    /**
     * @return void
     */
    public function test_participant_can_view_own_bid_only(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('participant');

        $other = User::factory()->create();
        $other->assignRole('participant');

        $own = AuctionBid::factory()->create(['user_id' => $owner->id]);
        $foreign = AuctionBid::factory()->create(['user_id' => $other->id]);

        $this->assertTrue($this->policy->view($owner, $own));
        $this->assertFalse($this->policy->view($owner, $foreign));
        $this->assertFalse($this->policy->viewAny($owner));
    }

    /**
     * @return void
     */
    public function test_admin_can_view_any_bid(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $bid = AuctionBid::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $bid));
    }
}
