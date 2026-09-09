<?php

namespace Tests\Feature\Admin;

use App\Jobs\GenerateAuctionProtocolJob;
use App\Models\AuctionProtocol;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты PDF-протокола аукциона (фаза 8.11).
 */
class AuctionProtocolTest extends TestCase
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
     * Гость не читает протоколы.
     *
     * @return void
     */
    public function test_guest_cannot_list_protocols(): void
    {
        $procedure = Procedure::factory()->auction()->create();

        $this->getJson("/api/admin/procedures/{$procedure->id}/auction/protocols")
            ->assertUnauthorized();
    }

    /**
     * Главный администратор ставит генерацию в очередь и видит список.
     *
     * @return void
     */
    public function test_super_admin_can_queue_protocol_generation(): void
    {
        Queue::fake();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'responsible_user_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/admin/procedures/{$procedure->id}/auction/protocols")
            ->assertOk();

        Queue::assertPushed(GenerateAuctionProtocolJob::class, function (GenerateAuctionProtocolJob $job) use ($procedure, $admin): bool {
            return $job->procedureId === $procedure->id && $job->generatedByUserId === $admin->id;
        });
    }

    /**
     * Job пишет PDF на диск и запись auction_protocols.
     *
     * @return void
     */
    public function test_generate_auction_protocol_job_stores_pdf(): void
    {
        Storage::fake('local');

        $procedure = Procedure::factory()->auction()->completed()->create();
        ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'name' => 'Лот 1',
        ]);

        (new GenerateAuctionProtocolJob($procedure->id, null))->handle();

        $this->assertDatabaseHas('auction_protocols', [
            'procedure_id' => $procedure->id,
            'generated_by' => null,
        ]);

        $protocol = AuctionProtocol::query()->where('procedure_id', $procedure->id)->firstOrFail();
        Storage::disk('local')->assertExists($protocol->file_path);
    }

    /**
     * Администратор скачивает готовый PDF.
     *
     * @return void
     */
    public function test_super_admin_can_download_protocol(): void
    {
        Storage::fake('local');

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $procedure = Procedure::factory()->auction()->create([
            'responsible_user_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        Storage::disk('local')->put('auction-protocols/test.pdf', '%PDF-1.4 test');

        $protocol = AuctionProtocol::query()->create([
            'procedure_id' => $procedure->id,
            'file_path' => 'auction-protocols/test.pdf',
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'template_version' => 1,
        ]);

        $this->actingAs($admin)
            ->get("/api/admin/procedures/{$procedure->id}/auction/protocols/{$protocol->id}/download")
            ->assertOk();
    }
}
