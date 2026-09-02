<?php

namespace App\Listeners;

use App\Events\ProcedureDocumentationChanged;
use App\Jobs\NotifyProcedureDocumentationChangedJob;

/**
 * Рассылка об изменении документации процедуры.
 */
class SendProcedureDocumentationChangedNotification
{
    /**
     * @param ProcedureDocumentationChanged $event Событие
     * @return void
     */
    public function handle(ProcedureDocumentationChanged $event): void
    {
        NotifyProcedureDocumentationChangedJob::dispatch(
            $event->procedure->id,
            $event->changeLogId,
        );
    }
}
