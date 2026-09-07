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
 * Без winner_user_id и без чужих ставок.
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

        $lots = $procedure->lots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->success(
            ParticipantAuctionLotResource::collection($lots)->resolve(),
            'Лоты аукциона.',
        );
    }

    /**
     * Открытый аукцион — любой участник; закрытый — только приглашённые/допущенные.
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
            ])
            ->exists();

        if (! $invited) {
            throw new AccessDeniedHttpException('Вы не приглашены к участию в этом аукционе.');
        }
    }
}
