<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Лот аукциона для участника (фаза 8.7): без чужих ставок и без чужих победителей.
 *
 * После завершения торгов отдаёт только флаг «я победил по этому лоту» (`i_am_winner`).
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
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $iAmWinner = $user !== null
            && $this->winner_user_id !== null
            && (int) $this->winner_user_id === (int) $user->id;

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
            'i_am_winner' => $iAmWinner,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
