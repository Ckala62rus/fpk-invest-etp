<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\AdminAuctionBidResource;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Админский список ставок по лоту (фаза 8.7).
 *
 * Полные данные участников; участникам этот endpoint недоступен.
 */
class AdminAuctionBidController extends ApiController
{
    /**
     * Все ставки лота с контактами авторов.
     *
     * @param Procedure $procedure Аукцион
     * @param int $lot ID лота
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function index(Procedure $procedure, int $lot): JsonResponse
    {
        $this->authorize('viewAny', AuctionBid::class);
        $this->assertCanAccess($procedure);

        $lotModel = ProcedureLot::query()
            ->where('procedure_id', $procedure->id)
            ->whereKey($lot)
            ->first();

        if ($lotModel === null) {
            throw new NotFoundHttpException('Лот не найден.');
        }

        $bids = $lotModel->bids()
            ->with('user.profile')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            AdminAuctionBidResource::collection($bids)->resolve(),
            'Ставки лота.',
        );
    }

    /**
     * super_admin и auditor — все процедуры; trade_admin — только свои.
     *
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

        throw new AccessDeniedHttpException('Недостаточно прав для просмотра ставок этой процедуры.');
    }
}
