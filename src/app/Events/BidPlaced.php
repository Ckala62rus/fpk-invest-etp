<?php

namespace App\Events;

use App\Models\AuctionBid;
use App\Models\Procedure;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ставка принята — тикер аукциона без личности участника (фаза 8.9).
 */
class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param int $procedureId ID аукциона
     * @param int $lotId ID лота
     * @param string $currentPrice Новая лучшая цена
     * @param string|null $endsAt ISO8601 срок (после возможного продления)
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public int $lotId,
        public string $currentPrice,
        public ?string $endsAt,
    ) {
    }

    /**
     * @param AuctionBid $bid Принятая ставка
     * @param Procedure $procedure Процедура после продления
     * @return self
     */
    public static function fromBid(AuctionBid $bid, Procedure $procedure): self
    {
        return new self(
            $procedure->id,
            $bid->lot_id,
            (string) $bid->amount,
            $procedure->ends_at?->toIso8601String(),
        );
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('auction.'.$this->procedureId)];
    }

    /**
     * Имя события для Laravel Echo.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'BidPlaced';
    }

    /**
     * Полезная нагрузка без user_id / email / организации.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'lot_id' => $this->lotId,
            'current_price' => $this->currentPrice,
            'ends_at' => $this->endsAt,
        ];
    }
}
