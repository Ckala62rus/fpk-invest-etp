<?php

namespace App\Services;

use App\Models\AuctionBid;
use App\Models\User;

/**
 * Правила видимости ставок аукциона (фаза 8.7).
 *
 * Участник видит только свои ставки и не получает чужие email, телефон, ФИО, организацию.
 * Админские роли видят полный список с контактными данными.
 */
class AuctionBidVisibilityService
{
    /**
     * Может ли пользователь видеть полный список ставок процедуры (админка).
     *
     * @param User $user Текущий пользователь
     * @return bool
     */
    public function canViewAnyAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'trade_admin', 'auditor']);
    }

    /**
     * Может ли пользователь видеть конкретную ставку.
     *
     * @param User $user Текущий пользователь
     * @param AuctionBid $bid Ставка
     * @return bool
     */
    public function canView(User $user, AuctionBid $bid): bool
    {
        if ($this->canViewAnyAdmin($user)) {
            return true;
        }

        return $user->hasRole('participant') && (int) $bid->user_id === (int) $user->id;
    }
}
