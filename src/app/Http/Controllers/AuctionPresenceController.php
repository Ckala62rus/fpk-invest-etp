<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\User;
use App\Services\AuctionPresenceService;
use Illuminate\Http\JsonResponse;

/**
 * Heartbeat присутствия участника на странице аукциона (фаза 8.8).
 *
 * Не отдаёт список других онлайн-пользователей.
 */
class AuctionPresenceController extends ApiController
{
    /**
     * @param AuctionPresenceService $presence Сервис сессий
     * @return void
     */
    public function __construct(
        private readonly AuctionPresenceService $presence,
    ) {
    }

    /**
     * Обновляет last_seen_at текущей сессии.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws DomainException
     */
    public function heartbeat(Procedure $procedure): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $session = $this->presence->heartbeat($procedure, $user);

        return $this->success(
            [
                'is_online' => $session->is_online,
                'last_seen_at' => $session->last_seen_at?->toIso8601String(),
            ],
            'Присутствие обновлено.',
        );
    }

    /**
     * Помечает участника офлайн.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws DomainException
     */
    public function leave(Procedure $procedure): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $this->presence->leave($procedure, $user);

        return $this->success(
            ['is_online' => false],
            'Вы вышли со страницы аукциона.',
        );
    }
}
