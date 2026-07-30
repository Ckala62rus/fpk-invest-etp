<?php

namespace Tests\Feature\Admin;

use App\Enums\ProcedureStatus;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты документов ТЗП (фаза 5.4).
 */
class ProcedureDocumentTest extends TestCase
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
        Storage::fake('local');
    }

    /**
     * trade_admin загружает документ в свой черновик.
     *
     * @return void
     */
    public function test_trade_admin_can_upload_document(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $procedure = Procedure::factory()->create([
            'responsible_user_id' => $admin->id,
            'status' => ProcedureStatus::Draft,
        ]);

        $file = UploadedFile::fake()->create('docs.pdf', 100, 'application/pdf');

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => $file,
            ])
            ->assertCreated()
            ->assertJsonPath('data.file_name', 'docs.pdf')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.uploaded_by', $admin->id);

        $this->assertDatabaseHas('procedure_documents', [
            'procedure_id' => $procedure->id,
            'file_name' => 'docs.pdf',
            'version' => 1,
        ]);
    }

    /**
     * Повторная загрузка увеличивает version.
     *
     * @return void
     */
    public function test_upload_increments_version(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->create(['status' => ProcedureStatus::Draft]);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.version', 1);

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.version', 2);
    }

    /**
     * У опубликованной процедуры документы менять нельзя.
     *
     * @return void
     */
    public function test_cannot_upload_to_published_procedure(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->published()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/procedures/'.$procedure->id.'/documents', [
                'document' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422);
    }
}
