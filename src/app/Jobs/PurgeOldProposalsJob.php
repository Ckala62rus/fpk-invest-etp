<?php

namespace App\Jobs;

use App\Models\Procedure;
use App\Models\Proposal;
use App\Services\SettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Удаляет КП (коммерческие предложения) старше срока хранения (фаза 11.2).
 */
class PurgeOldProposalsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param SettingsService $settings Глобальный срок, если у процедуры не задан
     * @return void
     */
    public function handle(SettingsService $settings): void
    {
        $defaultYears = $settings->getInt(SettingsService::PROPOSAL_RETENTION_YEARS, 5);

        $procedures = Procedure::query()
            ->whereNotNull('completed_at')
            ->get();

        $deleted = 0;

        foreach ($procedures as $procedure) {
            $years = $procedure->storage_years > 0 ? (int) $procedure->storage_years : $defaultYears;
            $cutoff = $procedure->completed_at->copy()->addYears($years);

            if ($cutoff->gt(now())) {
                continue;
            }

            $deleted += Proposal::query()
                ->where('procedure_id', $procedure->id)
                ->delete();
        }

        Log::channel('audit')->info('Очистка старых КП', ['deleted' => $deleted]);
    }
}
