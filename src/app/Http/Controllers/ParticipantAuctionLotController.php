<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Exceptions\DomainException;
use App\Http\Resources\ParticipantAuctionLotResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Список лотов аукциона для участника (фаза 8.7).
 *
 * Без чужих ставок и без чужих победителей; свой выигрыш — флаг i_am_winner.
 */
class ParticipantAuctionLotController extends ApiController
{
    /**
     * Лоты процедуры, доступные участнику для торгов.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($procedure->type !== ProcedureType::Auction) {
            throw new DomainException(
                message: 'Лоты торгов доступны только для процедур типа auction.',
                statusCode: 422,
            );
        }

        $this->assertCanViewLots($procedure, $user);

        $procedure->loadMissing('auctionSetting');

        $lots = $procedure->lots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $iAmWinner = $lots->contains(
            static fn ($lot): bool => (int) $lot->winner_user_id === (int) $user->id,
        );

        // meta: фаза торгов + свой итог (без чужих победителей)
        return response()->json([
            'success' => true,
            'message' => 'Лоты аукциона.',
            'data' => ParticipantAuctionLotResource::collection($lots)->resolve(),
            'meta' => [
                'status' => $procedure->status?->value,
                'status_label' => $procedure->status?->label(),
                'auction_trade_status' => $procedure->auctionTradeStatus()?->value,
                'auction_trade_status_label' => $procedure->auctionTradeStatusLabel(),
                'is_paused' => (bool) $procedure->auctionSetting?->is_paused,
                'ends_at' => $procedure->ends_at?->toIso8601String(),
                'i_am_winner' => $iAmWinner,
            ],
        ]);
    }

    /**
     * Открытый аукцион — любой участник; закрытый — приглашённые / допущенные / победители.
     *
     * @param Procedure $procedure Аукцион
     * @param User $user Участник
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanViewLots(Procedure $procedure, User $user): void
    {
        if ($procedure->visibility === ProcedureVisibility::Open) {
            return;
        }

        $invited = $procedure->participants()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                ParticipantStatus::Invited,
                ParticipantStatus::Admitted,
                ParticipantStatus::Winner,
            ])
            ->exists();

        if (! $invited) {
            throw new AccessDeniedHttpException('Вы не приглашены к участию в этом аукционе.');
        }
    }
}
