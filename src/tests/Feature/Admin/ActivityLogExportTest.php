<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CSV-экспорта журнала аудита (фаза 11.3).
 */
class ActivityLogExportTest extends TestCase
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
     * Гость не выгружает аудит.
     *
     * @return void
     */
    public function test_guest_cannot_export_activity_logs(): void
    {
        $this->getJson('/api/admin/activity-logs/export')->assertUnauthorized();
    }

    /**
     * Аудитор скачивает CSV.
     *
     * @return void
     */
    public function test_auditor_can_export_activity_logs_csv(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $this->actingAs($auditor)
            ->get('/api/admin/activity-logs/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
