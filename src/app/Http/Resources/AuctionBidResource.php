<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс ставки аукциона (своя ставка участника / админ).
 *
 * @mixin \App\Models\AuctionBid
 */
class AuctionBidResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'lot_id' => $this->lot_id,
            'amount' => $this->amount,
            'is_cancelled' => $this->is_cancelled,
            'cancel_reason' => $this->cancel_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
