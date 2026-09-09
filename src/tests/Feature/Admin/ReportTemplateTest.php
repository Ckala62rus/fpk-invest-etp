<?php

namespace Tests\Feature\Admin;

use App\Enums\ReportFormat;
use App\Jobs\GenerateReportJob;
use App\Models\Procedure;
use App\Models\ReportRun;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ReportQueryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты шаблонов и выгрузки отчётов (фаза 10).
 */
class ReportTemplateTest extends TestCase
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
     * Гость не читает шаблоны отчётов.
     *
     * @return void
     */
    public function test_guest_cannot_list_report_templates(): void
    {
        $this->getJson('/api/admin/report-templates')->assertUnauthorized();
    }

    /**
     * Главный администратор создаёт шаблон и ставит выгрузку в очередь.
     *
     * @return void
     */
    public function test_super_admin_can_create_template_and_queue_run(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $create = $this->actingAs($admin)
            ->postJson('/api/admin/report-templates', [
                'name' => 'Процедуры',
                'query_config' => ['source' => 'procedures'],
                'columns' => ['id', 'number', 'title', 'status'],
            ])
            ->assertCreated();

        $id = $create->json('data.id');

        $this->actingAs($admin)
            ->postJson("/api/admin/report-templates/{$id}/runs", [
                'format' => 'xlsx',
                'filters' => [],
            ])
            ->assertCreated();

        Queue::assertPushed(GenerateReportJob::class);
    }

    /**
     * Job формирует xlsx на диск.
     *
     * @return void
     */
    public function test_generate_report_job_writes_xlsx(): void
    {
        Storage::fake('local');

        /** @var User $admin */
        $admin = User::factory()->create();
        Procedure::factory()->create(['title' => 'Тестовая процедура']);

        $template = ReportTemplate::query()->create([
            'name' => 'Процедуры',
            'query_config' => ['source' => 'procedures'],
            'columns' => ['id', 'number', 'title'],
            'created_by' => $admin->id,
        ]);

        $run = ReportRun::query()->create([
            'template_id' => $template->id,
            'filters' => [],
            'format' => ReportFormat::Xlsx,
            'generated_by' => $admin->id,
            'generated_at' => now(),
        ]);

        (new GenerateReportJob($run->id))->handle(app(ReportQueryService::class));

        $run->refresh();
        $this->assertNotNull($run->file_path);
        Storage::disk('local')->assertExists($run->file_path);
    }

    /**
     * Аудитор читает шаблоны, но не создаёт.
     *
     * @return void
     */
    public function test_auditor_can_list_but_cannot_create_templates(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $this->actingAs($auditor)
            ->getJson('/api/admin/report-templates')
            ->assertOk();

        $this->actingAs($auditor)
            ->postJson('/api/admin/report-templates', [
                'name' => 'Запрещено',
                'query_config' => ['source' => 'procedures'],
                'columns' => ['id'],
            ])
            ->assertForbidden();
    }
}
