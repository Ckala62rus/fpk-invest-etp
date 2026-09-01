<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalVisibilityService;

/**
 * Политика доступа к коммерческим предложениям (фаза 6.5).
 */
class ProposalPolicy
{
    /**
     * @param ProposalVisibilityService $visibility Сервис правил видимости
     * @return void
     */
    public function __construct(
        private readonly ProposalVisibilityService $visibility,
    ) {
    }

    /**
     * Список заявок процедуры — админские роли.
     *
     * @param User $user Пользователь
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'trade_admin', 'auditor']);
    }

    /**
     * Просмотр заявки: владелец или админ процедуры.
     *
     * @param User $user Пользователь
     * @param Proposal $proposal Заявка
     * @return bool
     */
    public function view(User $user, Proposal $proposal): bool
    {
        return $this->visibility->canView($user, $proposal);
    }
}
