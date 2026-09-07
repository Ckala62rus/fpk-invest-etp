<?php

namespace App\Policies;

use App\Models\AuctionBid;
use App\Models\User;
use App\Services\AuctionBidVisibilityService;

/**
 * Политика доступа к ставкам аукциона (фаза 8.7).
 */
class AuctionBidPolicy
{
    /**
     * @param AuctionBidVisibilityService $visibility Правила видимости
     * @return void
     */
    public function __construct(
        private readonly AuctionBidVisibilityService $visibility,
    ) {
    }

    /**
     * Полный список ставок — только админские роли.
     *
     * @param User $user Пользователь
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->visibility->canViewAnyAdmin($user);
    }

    /**
     * Просмотр ставки: владелец или админ.
     *
     * @param User $user Пользователь
     * @param AuctionBid $bid Ставка
     * @return bool
     */
    public function view(User $user, AuctionBid $bid): bool
    {
        return $this->visibility->canView($user, $bid);
    }
}
