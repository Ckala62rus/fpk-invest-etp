<?php

namespace App\Events;

use App\Models\AuctionBid;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ставка аукциона отменена администратором (фаза 7.3 / 8.6 / 8.9).
 *
 * Слушатели писем получают модель ставки; WebSocket — только лот и новую цену.
 */
class BidCancelled implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param AuctionBid $bid Отменённая ставка (с лотом после пересчёта цены)
     * @return void
     */
    public function __construct(
        public AuctionBid $bid,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('auction.'.$this->bid->procedure_id)];
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'BidCancelled';
    }

    /**
     * Без user_id, email и организации автора ставки.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->bid->loadMissing('lot');

        $currentPrice = $this->bid->lot?->current_price ?? $this->bid->lot?->start_price;

        return [
            'lot_id' => $this->bid->lot_id,
            'bid_id' => $this->bid->id,
            'current_price' => $currentPrice !== null ? (string) $currentPrice : null,
        ];
    }
}
