<?php

namespace App\Http\Controllers;

use App\Actions\Auction\PlaceBidAction;
use App\Exceptions\DomainException;
use App\Http\Requests\Api\PlaceBidRequest;
use App\Http\Resources\AuctionBidResource;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Подача ставок участником на аукционе (фаза 8.3).
 */
class AuctionBidController extends ApiController
{
    /**
     * @param PlaceBidAction $placeBid Действие подачи ставки
     * @return void
     */
    public function __construct(
        private readonly PlaceBidAction $placeBid,
    ) {
    }

    /**
     * Подаёт ставку на лот.
     *
     * @param PlaceBidRequest $request Валидированная сумма
     * @param Procedure $procedure Аукцион
     * @param int $lot ID лота
     * @return JsonResponse
     *
     * @throws DomainException|NotFoundHttpException
     */
    public function store(PlaceBidRequest $request, Procedure $procedure, int $lot): JsonResponse
    {
        $lotModel = ProcedureLot::query()
            ->where('procedure_id', $procedure->id)
            ->whereKey($lot)
            ->first();

        if ($lotModel === null) {
            throw new NotFoundHttpException('Лот не найден.');
        }

        /** @var User $participant */
        $participant = $request->user();

        $bid = $this->placeBid->execute(
            $procedure,
            $lotModel,
            $participant,
            (string) $request->validated('amount'),
            $request->ip(),
        );

        return $this->created(
            new AuctionBidResource($bid),
            'Ставка принята.',
        );
    }

    /**
     * Список своих ставок по лоту (чужие скрыты — фаза 8.7 полностью).
     *
     * @param Procedure $procedure Аукцион
     * @param int $lot ID лота
     * @return JsonResponse
     *
     * @throws NotFoundHttpException
     */
    public function index(Procedure $procedure, int $lot): JsonResponse
    {
        $lotModel = ProcedureLot::query()
            ->where('procedure_id', $procedure->id)
            ->whereKey($lot)
            ->first();

        if ($lotModel === null) {
            throw new NotFoundHttpException('Лот не найден.');
        }

        /** @var User $user */
        $user = request()->user();

        $bids = $lotModel->bids()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        foreach ($bids as $bid) {
            $this->authorize('view', $bid);
        }

        return $this->success(
            AuctionBidResource::collection($bids)->resolve(),
            'Ваши ставки по лоту.',
        );
    }
}
