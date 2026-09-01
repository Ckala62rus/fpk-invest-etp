<?php

namespace Tests\Feature\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Jobs\NotifyProcedureDocumentationChangedJob;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты согласования изменений документации (фаза 6.6–6.7).
 */
class ProcedureChangeApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
        Storage::fake('local');
    }

    /**
     * Загрузка документа на accepting создаёт pending change log.
     *
     * @return void
     */
    public function test_upload_on_accepting_creates_pending_change_log(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'responsible_user_id' => $admin->id,
            'ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => UploadedFile::fake()->create('new.pdf', 50, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Документ загружен и ожидает согласования.');

        $this->assertDatabaseHas('procedure_change_logs', [
            'procedure_id' => $procedure->id,
            'approval_status' => ApprovalStatus::Pending->value,
        ]);
    }

    /**
     * Менее чем за 2 дня до ends_at загрузка запрещена.
     *
     * @return void
     */
    public function test_cannot_upload_documents_too_close_to_deadline(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->accepting()->create([
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Редактирование документации запрещено менее чем за 2 дн. до окончания приёма.',
            );
    }

    /**
     * Аудитор согласует изменение и продлевает ends_at.
     *
     * @return void
     */
    public function test_auditor_can_approve_and_extend_deadline(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $procedure = Procedure::factory()->accepting()->create([
            'ends_at' => now()->addDays(10),
        ]);

        $log = ProcedureChangeLog::query()->create([
            'procedure_id' => $procedure->id,
            'changed_by' => User::factory()->create()->id,
            'change_summary' => 'Тест',
            'diff' => ['document_id' => 1],
            'approval_status' => ApprovalStatus::Pending,
        ]);

        $oldEndsAt = $procedure->ends_at->copy();

        $this->actingAs($auditor)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/change-logs/'.$log->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.approval_status', ApprovalStatus::Approved->value);

        $procedure->refresh();
        $this->assertTrue($procedure->ends_at->greaterThan($oldEndsAt));

        Queue::assertPushed(NotifyProcedureDocumentationChangedJob::class);
    }

    /**
     * super_admin может обновить doc_edit_deadline_days.
     *
     * @return void
     */
    public function test_super_admin_can_update_settings(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'doc_edit_deadline_days' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.doc_edit_deadline_days', 3);
    }
}
