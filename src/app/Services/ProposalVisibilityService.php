<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\User;

/**
 * Правила видимости содержимого КП (фаза 6.5).
 *
 * Администраторы площадки видят полное КП (поля и документы) сразу после подачи —
 * иначе нельзя рассмотреть заявку и вынести решение о допуске до дедлайна.
 * Участник всегда видит только своё предложение целиком.
 */
class ProposalVisibilityService
{
    /**
     * Истёк ли срок приёма коммерческих предложений.
     *
     * @param Proposal $proposal Заявка с загруженной procedure
     * @return bool
     */
    public function isDeadlinePassed(Proposal $proposal): bool
    {
        $endsAt = $proposal->procedure?->ends_at;

        return $endsAt !== null && $endsAt->isPast();
    }

    /**
     * Может ли пользователь видеть полное содержимое заявки (поля, документы).
     *
     * @param User|null $user Текущий пользователь
     * @param Proposal $proposal Заявка
     * @return bool
     */
    public function canViewFullContent(?User $user, Proposal $proposal): bool
    {
        if ($user === null) {
            return false;
        }

        if ((int) $proposal->user_id === (int) $user->id) {
            return true;
        }

        // Админ / аудитор / ответственный trade_admin — полное КП до дедлайна
        return $this->canAccessAsAdmin($user, $proposal);
    }

    /**
     * Имеет ли пользователь доступ к заявке (хотя бы в ограниченном виде).
     *
     * @param User|null $user Текущий пользователь
     * @param Proposal $proposal Заявка
     * @return bool
     */
    public function canView(?User $user, Proposal $proposal): bool
    {
        if ($user === null) {
            return false;
        }

        if ((int) $proposal->user_id === (int) $user->id) {
            return true;
        }

        return $this->canAccessAsAdmin($user, $proposal);
    }

    /**
     * Админские роли с доступом к заявкам процедуры.
     *
     * @param User $user Пользователь
     * @param Proposal $proposal Заявка с procedure
     * @return bool
     */
    private function canAccessAsAdmin(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('auditor')) {
            return true;
        }

        if (
            $user->hasRole('trade_admin')
            && (int) $proposal->procedure?->responsible_user_id === (int) $user->id
        ) {
            return true;
        }

        return false;
    }
}
