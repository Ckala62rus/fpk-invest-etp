<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс настроек электронного аукциона (фаза 8.1).
 *
 * @mixin \App\Models\AuctionSetting
 */
class AuctionSettingResource extends JsonResource
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
            'bid_mode' => $this->bid_mode?->value,
            'bid_mode_label' => $this->bid_mode?->label(),
            'auction_mode' => $this->auction_mode?->value,
            'auction_mode_label' => $this->auction_mode?->label(),
            'extension_minutes' => $this->extension_minutes,
            'extension_trigger_minutes' => $this->extension_trigger_minutes,
            'idle_timeout_minutes' => $this->idle_timeout_minutes,
            'forbid_equal_bids' => $this->forbid_equal_bids,
            'winner_mode' => $this->winner_mode?->value,
            'winner_mode_label' => $this->winner_mode?->label(),
            'only_admitted_from_rfp' => $this->only_admitted_from_rfp,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
