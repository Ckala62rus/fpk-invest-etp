<?php

namespace App\Jobs;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Models\EmailSendLog;
use App\Models\Procedure;
use App\Enums\EmailSendStatus;
use App\Services\NotificationMailService;
use App\Services\ProcedureNotificationRecipientService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Приглашения на аукцион в день публикации и в день торгов (фаза 8.12).
 *
 * Напоминание −1 час / −1 день — SendAuctionRemindersJob (фаза 7.4).
 */
class SendAuctionInviteScheduleJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param NotificationMailService $mailService Письма
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    public function handle(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $this->sendForPublishedToday($mailService, $recipients);
        $this->sendForAuctionDay($mailService, $recipients);
    }

    /**
     * @param NotificationMailService $mailService Сервис
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    private function sendForPublishedToday(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $procedures = Procedure::query()
            ->where('type', ProcedureType::Auction)
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$from, $to])
            ->get();

        $this->sendBatch(
            $mailService,
            $recipients,
            $procedures,
            NotificationTemplateCode::AuctionInviteCreated,
        );
    }

    /**
     * @param NotificationMailService $mailService Сервис
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    private function sendForAuctionDay(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $procedures = Procedure::query()
            ->where('type', ProcedureType::Auction)
            ->whereIn('status', [
                ProcedureStatus::AuctionPending,
                ProcedureStatus::InProgress,
            ])
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$from, $to])
            ->get();

        $this->sendBatch(
            $mailService,
            $recipients,
            $procedures,
            NotificationTemplateCode::AuctionInviteAuctionDay,
        );
    }

    /**
     * @param NotificationMailService $mailService Сервис
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @param \Illuminate\Support\Collection<int, Procedure> $procedures Процедуры
     * @param string $templateCode Код шаблона
     * @return void
     */
    private function sendBatch(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
        $procedures,
        string $templateCode,
    ): void {
        foreach ($procedures as $procedure) {
            if ($this->alreadySent($templateCode, $procedure->id)) {
                continue;
            }

            $mailService->sendToUsers(
                $templateCode,
                $recipients->recipientsForProcedure($procedure),
                ProcedureNotificationPayload::forProcedure($procedure),
            );
        }
    }

    /**
     * @param string $templateCode Код
     * @param int $procedureId ID
     * @return bool
     */
    private function alreadySent(string $templateCode, int $procedureId): bool
    {
        return EmailSendLog::query()
            ->whereHas('template', static function ($query) use ($templateCode): void {
                $query->where('code', $templateCode);
            })
            ->where('status', EmailSendStatus::Sent)
            ->where('payload->procedure->id', $procedureId)
            ->exists();
    }
}
