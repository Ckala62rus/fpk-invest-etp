<?php

namespace Tests\Feature\Api;

use App\Models\ClassifierCategory;
use App\Models\CompanyGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты подписок участника (фаза 3.4).
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_read_subscriptions(): void
    {
        $this->getJson('/api/subscriptions')->assertUnauthorized();
    }

    public function test_participant_can_sync_subscriptions(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $group = CompanyGroup::factory()->create();
        $category = ClassifierCategory::factory()->create(['company_group_id' => $group->id]);

        $this->actingAs($user)
            ->putJson('/api/subscriptions', [
                'category_ids' => [$category->id],
                'company_group_ids' => [$group->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonPath('data.company_groups.0.id', $group->id);

        $this->actingAs($user)
            ->getJson('/api/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(1, 'data.company_groups');

        $this->actingAs($user)
            ->putJson('/api/subscriptions', [
                'category_ids' => [],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data.categories')
            ->assertJsonCount(1, 'data.company_groups');
    }

    public function test_invalid_category_id_returns_422(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->putJson('/api/subscriptions', [
                'category_ids' => [999999],
            ])
            ->assertUnprocessable();
    }
}
