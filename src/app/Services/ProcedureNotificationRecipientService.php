<?php

namespace App\Services;

use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Получатели уведомлений по процедуре с учётом подписок (фаза 7.6).
 */
class ProcedureNotificationRecipientService
{
    /**
     * Уникальные email участников для рассылки по процедуре.
     *
     * Закрытая — приглашённые участники; открытая — подписчики категории классификатора.
     *
     * @param Procedure $procedure ТЗП
     * @return Collection<int, User>
     */
    public function recipientsForProcedure(Procedure $procedure): Collection
    {
        if ($procedure->visibility === ProcedureVisibility::Closed) {
            $procedure->loadMissing(['participants.user.notificationSettings']);

            return $procedure->participants
                ->pluck('user')
                ->filter(fn (?User $user) => $user !== null && $user->email !== null)
                ->filter(fn (User $user) => ! $user->notificationSettings?->all_disabled)
                ->unique('id')
                ->values();
        }

        return User::query()
            ->role('participant')
            ->whereHas('categorySubscriptions', static function ($q) use ($procedure): void {
                $q->where('classifier_categories.id', $procedure->classifier_category_id);
            })
            ->whereDoesntHave('notificationSettings', static function ($q): void {
                $q->where('all_disabled', true);
            })
            ->get();
    }
}
