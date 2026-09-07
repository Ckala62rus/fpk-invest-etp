<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auction\CancelBidAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\CancelBidRequest;
use App\Http\Resources\AuctionBidResource;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Отмена ставки аукциона администратором (фаза 8.6).
 */
class AuctionBidCancelController extends ApiController
{
    /**
     * @param CancelBidAction $cancelBid Действие отмены
     * @return void
     */
    public function __construct(
        private readonly CancelBidAction $cancelBid,
    ) {
    }

    /**
     * Отменяет ставку с обязательной причиной.
     *
     * @param CancelBidRequest $request Причина
     * @param Procedure $procedure Аукцион
     * @param int $bid ID ставки
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function store(CancelBidRequest $request, Procedure $procedure, int $bid): JsonResponse
    {
        $this->assertCanManage($procedure);

        $model = AuctionBid::query()
            ->where('procedure_id', $procedure->id)
            ->whereKey($bid)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Ставка не найдена.');
        }

        /** @var User $admin */
        $admin = $request->user();

        $cancelled = $this->cancelBid->execute(
            $procedure,
            $model,
            $admin,
            $request->validated('reason'),
        );

        return $this->success(
            new AuctionBidResource($cancelled),
            'Ставка отменена.',
        );
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanManage(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для отмены ставки.');
    }
}
