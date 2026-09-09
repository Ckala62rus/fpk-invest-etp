<?php

namespace App\Jobs;

use App\Models\EvaluationSurvey;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Цепочка напоминаний по опросу: 7д → 3д → 2д → 1д → ежедневно (фаза 9.3).
 *
 * reminder_stage: 0 после отправки, затем 1..4 по порогам, 5+ ежедневно.
 */
class SendEvaluationRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Дни с sent_at для стадий 1–4.
     *
     * @var array<int, int>
     */
    private const STAGE_DAYS = [
        1 => 7,
        2 => 10,
        3 => 12,
        4 => 13,
    ];

    /**
     * @param NotificationMailService $mailService Письма
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $surveys = EvaluationSurvey::query()
            ->with('procedure')
            ->whereNull('completed_at')
            ->whereNotNull('sent_at')
            ->get();

        foreach ($surveys as $survey) {
            $procedure = $survey->procedure;
            if ($procedure === null) {
                continue;
            }

            $email = $procedure->customer_contact_email;
            if ($email === null || $email === '') {
                continue;
            }

            $days = $survey->sent_at->diffInDays(now());
            $nextStage = $survey->reminder_stage + 1;

            $shouldSend = false;
            if ($survey->reminder_stage < 4) {
                $threshold = self::STAGE_DAYS[$nextStage] ?? null;
                $shouldSend = $threshold !== null && $days >= $threshold;
            } else {
                $shouldSend = $days >= 14;
            }

            if (! $shouldSend) {
                continue;
            }

            $data = ProcedureNotificationPayload::forProcedure($procedure);
            $data['survey'] = [
                'url' => rtrim((string) env('FRONTEND_URL', config('app.url')), '/').'/evaluation/'.$survey->token,
            ];

            $mailService->send(
                NotificationTemplateCode::EvaluationSurveyReminder,
                $email,
                $data,
            );

            $survey->update([
                'reminder_stage' => min($survey->reminder_stage + 1, 5),
            ]);
        }
    }
}
