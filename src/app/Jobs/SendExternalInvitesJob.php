<?php

namespace App\Jobs;

use App\Models\ExternalInviteBatch;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Отправка внешних приглашений на процедуру (фаза 6.8 / 7.3).
 */
class SendExternalInvitesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $batchId ID ExternalInviteBatch
     * @return void
     */
    public function __construct(
        public int $batchId,
    ) {
    }

    /**
     * @param NotificationMailService $mailService Сервис отправки
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $batch = ExternalInviteBatch::query()
            ->with('procedure')
            ->find($this->batchId);

        if ($batch === null || $batch->procedure === null) {
            return;
        }

        $data = ProcedureNotificationPayload::forProcedure($batch->procedure);

        foreach ($batch->emails as $email) {
            $mailService->send(
                NotificationTemplateCode::ExternalProcedureInvite,
                $email,
                $data,
            );
        }

        $batch->update(['sent_at' => now()]);
    }
}
