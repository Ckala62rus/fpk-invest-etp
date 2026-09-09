<?php

namespace App\Jobs;

use App\Enums\ProcedureStatus;
use App\Models\EvaluationSurvey;
use App\Models\Procedure;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

/**
 * Создаёт опрос качества через месяц после завершения ТЗП (фаза 9.2).
 */
class CreateEvaluationSurveysJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param NotificationMailService $mailService Письма заказчику
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $deadline = now()->subMonth();

        $procedures = Procedure::query()
            ->where('status', ProcedureStatus::Completed)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $deadline)
            ->whereDoesntHave('evaluationSurveys')
            ->get();

        foreach ($procedures as $procedure) {
            $survey = EvaluationSurvey::query()->create([
                'procedure_id' => $procedure->id,
                'token' => Str::random(48),
                'sent_at' => now(),
                'reminder_stage' => 0,
            ]);

            $email = $procedure->customer_contact_email;
            if ($email === null || $email === '') {
                continue;
            }

            $data = ProcedureNotificationPayload::forProcedure($procedure);
            $data['survey'] = [
                'url' => rtrim((string) env('FRONTEND_URL', config('app.url')), '/').'/evaluation/'.$survey->token,
            ];

            $mailService->send(
                NotificationTemplateCode::EvaluationSurvey,
                $email,
                $data,
            );
        }
    }
}
