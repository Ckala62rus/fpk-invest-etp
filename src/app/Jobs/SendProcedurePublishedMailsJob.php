<?php

namespace App\Jobs;

use App\Models\Procedure;
use App\Services\NotificationMailService;
use App\Services\ProcedureNotificationRecipientService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Рассылка писем о публикации ТЗП (фаза 5.7 / 7.3 / 7.6).
 */
class SendProcedurePublishedMailsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $procedureId Идентификатор ТЗП
     * @return void
     */
    public function __construct(
        public int $procedureId,
    ) {
    }

    /**
     * @param NotificationMailService $mailService Сервис отправки
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    public function handle(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $procedure = Procedure::query()
            ->with(['category', 'company'])
            ->find($this->procedureId);

        if ($procedure === null) {
            return;
        }

        $mailService->sendToUsers(
            NotificationTemplateCode::ProcedurePublished,
            $recipients->recipientsForProcedure($procedure),
            ProcedureNotificationPayload::forProcedure($procedure),
        );
    }
}
