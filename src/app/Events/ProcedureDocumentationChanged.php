<?php

namespace App\Events;

use App\Models\Procedure;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Документация опубликованной процедуры изменена и согласована (фаза 7.3).
 */
class ProcedureDocumentationChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param Procedure $procedure Процедура
     * @param int $changeLogId ID записи change log
     * @return void
     */
    public function __construct(
        public Procedure $procedure,
        public int $changeLogId,
    ) {
    }
}
