<?php

namespace App\Jobs;

use App\Models\Procedure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Уведомление участников об изменении документации (фаза 6.6).
 *
 * Полная рассылка — в фазе 7; сейчас логируем для аудита.
 */
class NotifyProcedureDocumentationChangedJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $procedureId ID процедуры
     * @param int $changeLogId ID записи change log
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public int $changeLogId,
    ) {
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $procedure = Procedure::query()->find($this->procedureId);

        if ($procedure === null) {
            return;
        }

        Log::info('Procedure documentation changed notification queued', [
            'procedure_id' => $this->procedureId,
            'change_log_id' => $this->changeLogId,
            'number' => $procedure->number,
        ]);
    }
}
