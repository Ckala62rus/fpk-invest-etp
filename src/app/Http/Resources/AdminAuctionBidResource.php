<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Админский ресурс ставки аукциона (фаза 8.7).
 *
 * Содержит автора ставки и контакты — только для super_admin / trade_admin / auditor.
 *
 * @mixin \App\Models\AuctionBid
 */
class AdminAuctionBidResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        $winnerUserId = $this->lot?->winner_user_id;
        $isLotWinner = $winnerUserId !== null && (int) $winnerUserId === (int) $this->user_id;

        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'lot_id' => $this->lot_id,
            'user_id' => $this->user_id,
            'user' => $user === null ? null : [
                'id' => $user->id,
                'inn' => $user->inn,
                'email' => $user->email,
                'phone' => $user->profile?->phone,
                'organization_name' => $user->profile?->name,
            ],
            'amount' => $this->amount,
            'is_cancelled' => $this->is_cancelled,
            'is_lot_winner' => $isLotWinner,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
