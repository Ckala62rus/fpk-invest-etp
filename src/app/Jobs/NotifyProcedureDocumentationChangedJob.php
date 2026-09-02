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
 * Уведомление участников об изменении документации (фаза 6.6 / 7.3).
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
     * @param NotificationMailService $mailService Сервис отправки
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    public function handle(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $procedure = Procedure::query()->find($this->procedureId);

        if ($procedure === null) {
            return;
        }

        $data = array_merge(
            ProcedureNotificationPayload::forProcedure($procedure),
            ['change_log_id' => $this->changeLogId],
        );

        $mailService->sendToUsers(
            NotificationTemplateCode::ProcedureDocumentationChanged,
            $recipients->recipientsForProcedure($procedure),
            $data,
        );
    }
}
