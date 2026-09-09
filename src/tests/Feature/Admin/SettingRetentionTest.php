<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты срока хранения КП в глобальных настройках (фаза 11.2).
 */
class SettingRetentionTest extends TestCase
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
     * Главный администратор меняет срок хранения КП (коммерческих предложений).
     *
     * @return void
     */
    public function test_super_admin_can_update_proposal_retention_years(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'proposal_retention_years' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('data.proposal_retention_years', 7);

        $this->assertSame(7, app(SettingsService::class)->getInt(SettingsService::PROPOSAL_RETENTION_YEARS, 5));
    }
}
