<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Лот аукциона для участника (фаза 8.7): без победителя и без чужих ставок.
 *
 * @mixin \App\Models\ProcedureLot
 */
class ParticipantAuctionLotResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'name' => $this->name,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'start_price' => $this->start_price,
            'bid_step' => $this->bid_step,
            'current_price' => $this->current_price,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
