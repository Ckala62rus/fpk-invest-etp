<?php

namespace App\Jobs;

use App\Enums\EmailSendStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Models\EmailSendLog;
use App\Models\Procedure;
use App\Services\NotificationMailService;
use App\Services\ProcedureNotificationRecipientService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Напоминания об аукционе за 1 день и за 1 час до starts_at (фаза 7.4).
 */
class SendAuctionRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Окно ±7 минут вокруг целевого времени (job запускается каждые 15 мин).
     */
    private const WINDOW_MINUTES = 7;

    /**
     * @param NotificationMailService $mailService Отправка писем
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @return void
     */
    public function handle(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
    ): void {
        $this->sendReminders(
            $mailService,
            $recipients,
            NotificationTemplateCode::AuctionReminderOneDay,
            now()->addDay(),
        );

        $this->sendReminders(
            $mailService,
            $recipients,
            NotificationTemplateCode::AuctionReminderOneHour,
            now()->addHour(),
        );
    }

    /**
     * @param NotificationMailService $mailService Сервис отправки
     * @param ProcedureNotificationRecipientService $recipients Получатели
     * @param string $templateCode Код шаблона
     * @param Carbon $targetStartsAt Целевое время начала аукциона
     * @return void
     */
    private function sendReminders(
        NotificationMailService $mailService,
        ProcedureNotificationRecipientService $recipients,
        string $templateCode,
        Carbon $targetStartsAt,
    ): void {
        $from = $targetStartsAt->copy()->subMinutes(self::WINDOW_MINUTES);
        $to = $targetStartsAt->copy()->addMinutes(self::WINDOW_MINUTES);

        $procedures = Procedure::query()
            ->where('type', ProcedureType::Auction)
            ->whereIn('status', [
                ProcedureStatus::AuctionPending,
                ProcedureStatus::InProgress,
            ])
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$from, $to])
            ->get();

        foreach ($procedures as $procedure) {
            if ($this->reminderAlreadySent($templateCode, $procedure->id)) {
                continue;
            }

            $data = ProcedureNotificationPayload::forProcedure($procedure);

            $mailService->sendToUsers(
                $templateCode,
                $recipients->recipientsForProcedure($procedure),
                $data,
            );
        }
    }

    /**
     * @param string $templateCode Код шаблона
     * @param int $procedureId ID процедуры
     * @return bool
     */
    private function reminderAlreadySent(string $templateCode, int $procedureId): bool
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
