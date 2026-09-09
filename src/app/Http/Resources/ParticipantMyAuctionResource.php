<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Аукцион в списке «мои аукционы» кабинета участника.
 *
 * Не раскрывает чужих победителей — только признак «я победил» и свои выигранные лоты.
 *
 * @mixin \App\Models\Procedure
 */
class ParticipantMyAuctionResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $userId = $user?->id;

        $wonLots = [];
        if ($userId !== null) {
            foreach ($this->lots as $lot) {
                if ((int) $lot->winner_user_id === (int) $userId) {
                    $wonLots[] = [
                        'id' => $lot->id,
                        'name' => $lot->name,
                        'current_price' => $lot->current_price,
                    ];
                }
            }
        }

        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'auction_trade_status' => $this->auctionTradeStatus()?->value,
            'auction_trade_status_label' => $this->auctionTradeStatusLabel(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_winner' => $wonLots !== [],
            'won_lots' => $wonLots,
        ];
    }
}
