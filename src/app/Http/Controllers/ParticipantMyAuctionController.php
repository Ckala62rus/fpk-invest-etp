<?php

namespace App\Http\Controllers;

use App\Enums\ProcedureType;
use App\Http\Resources\ParticipantMyAuctionResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Список аукционов участника в личном кабинете (ставки / приглашение / победа).
 */
class ParticipantMyAuctionController extends ApiController
{
    /**
     * Аукционы, где текущий пользователь ставил, приглашён или отмечен участником.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $procedures = Procedure::query()
            ->with(['auctionSetting', 'lots'])
            ->where('type', ProcedureType::Auction)
            ->where(static function ($query) use ($user): void {
                $query
                    ->whereHas('bids', static function ($bids) use ($user): void {
                        $bids->where('user_id', $user->id);
                    })
                    ->orWhereHas('participants', static function ($participants) use ($user): void {
                        $participants->where('user_id', $user->id);
                    });
            })
            ->orderByDesc('id')
            ->get();

        return $this->success(
            ParticipantMyAuctionResource::collection($procedures)->resolve(),
            'Мои аукционы.',
        );
    }
}
