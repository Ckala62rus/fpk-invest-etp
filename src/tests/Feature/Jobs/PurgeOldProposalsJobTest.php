<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PurgeOldProposalsJob;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты очистки КП по сроку хранения (фаза 11.2).
 */
class PurgeOldProposalsJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Удаляет КП завершённой процедуры после истечения срока хранения.
     *
     * @return void
     */
    public function test_deletes_proposals_past_retention(): void
    {
        $procedure = Procedure::factory()->completed()->create([
            'completed_at' => now()->subYears(6),
            'storage_years' => 5,
        ]);

        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        (new PurgeOldProposalsJob())->handle(app(SettingsService::class));

        $this->assertDatabaseMissing('proposals', ['id' => $proposal->id]);
    }

    /**
     * Не трогает КП, пока срок хранения не истёк.
     *
     * @return void
     */
    public function test_keeps_proposals_within_retention(): void
    {
        $procedure = Procedure::factory()->completed()->create([
            'completed_at' => now()->subYear(),
            'storage_years' => 5,
        ]);

        $proposal = Proposal::factory()->submitted()->create([
            'procedure_id' => $procedure->id,
        ]);

        (new PurgeOldProposalsJob())->handle(app(SettingsService::class));

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id]);
    }
}
