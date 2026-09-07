<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Models\Procedure;
use App\Models\User;
use App\Services\AuctionPresenceService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Админский снимок присутствия на аукционе (фаза 8.8).
 */
class AdminAuctionPresenceController extends ApiController
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
     * Кто онлайн и кто из приглашённых не заходил.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function show(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        return $this->success(
            $this->presence->adminSnapshot($procedure),
            'Присутствие на аукционе.',
        );
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanAccess(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasRole('super_admin') || $user->hasRole('auditor')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для просмотра присутствия.');
    }
}
