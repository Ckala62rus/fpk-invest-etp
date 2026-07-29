<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс лота ТЗП для админского API.
 *
 * @mixin \App\Models\ProcedureLot
 */
class ProcedureLotResource extends JsonResource
{
    /**
     * Преобразует лот в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
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
            'winner_user_id' => $this->winner_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
