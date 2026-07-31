<?php

namespace App\Listeners;

use App\Events\ProcedurePublished;
use App\Jobs\SendProcedurePublishedMailsJob;

/**
 * После публикации ТЗП ставит в очередь Job рассылки уведомлений.
 */
class SendProcedurePublishedNotifications
{
    /**
     * Обрабатывает событие ProcedurePublished.
     *
     * @param ProcedurePublished $event Событие публикации
     * @return void
     */
    public function handle(ProcedurePublished $event): void
    {
        SendProcedurePublishedMailsJob::dispatch($event->procedure->id);
    }
}
