<?php

namespace App\Jobs;

use App\Enums\ProcedureVisibility;
use App\Mail\ProcedurePublishedMail;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Рассылка писем о публикации ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.7: для закрытых — приглашённые участники; для открытых — подписчики категории.
 */
class SendProcedurePublishedMailsJob implements ShouldQueue
{
    use Queueable;

    /**
     * ID опубликованной процедуры.
     *
     * @var int
     */
    public int $procedureId;

    /**
     * @param int $procedureId Идентификатор ТЗП
     * @return void
     */
    public function __construct(int $procedureId)
    {
        $this->procedureId = $procedureId;
    }

    /**
     * Отправляет письма получателям публикации.
     *
     * @return void
     */
    public function handle(): void
    {
        $procedure = Procedure::query()
            ->with(['participants.user', 'category'])
            ->find($this->procedureId);

        if ($procedure === null) {
            return;
        }

        foreach ($this->resolveRecipients($procedure) as $email) {
            Mail::to($email)->send(new ProcedurePublishedMail($procedure));
        }
    }

    /**
     * Собирает уникальные email получателей.
     *
     * @param Procedure $procedure Опубликованная ТЗП
     * @return list<string>
     */
    private function resolveRecipients(Procedure $procedure): array
    {
        if ($procedure->visibility === ProcedureVisibility::Closed) {
            return $procedure->participants
                ->pluck('user.email')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        // Открытая: участники с подпиской на категорию классификатора
        return User::query()
            ->role('participant')
            ->whereHas('categorySubscriptions', static function ($q) use ($procedure): void {
                $q->where('classifier_categories.id', $procedure->classifier_category_id);
            })
            ->whereDoesntHave('notificationSettings', static function ($q): void {
                $q->where('all_disabled', true);
            })
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
