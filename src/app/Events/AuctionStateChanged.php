<?php

namespace App\Events;

use App\Models\Procedure;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Изменился статус торгов: старт, пауза, возобновление, финиш (фаза 8.9).
 */
class AuctionStateChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param int $procedureId ID аукциона
     * @param string $status Статус процедуры (in_progress / completed / …)
     * @param string|null $statusLabel Подпись статуса процедуры
     * @param bool $isPaused Пауза
     * @param string|null $auctionTradeStatus Фаза торгов: pending|running|paused|finished|cancelled
     * @param string|null $auctionTradeStatusLabel Подпись фазы торгов для UI
     * @param string $action start|pause|resume|finish
     * @param string $actionLabel Подпись действия на русском
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public string $status,
        public ?string $statusLabel,
        public bool $isPaused,
        public ?string $auctionTradeStatus,
        public ?string $auctionTradeStatusLabel,
        public string $action,
        public string $actionLabel,
    ) {
    }

    /**
     * @param Procedure $procedure Аукцион
     * @param string $action Код действия
     * @return self
     */
    public static function fromProcedure(Procedure $procedure, string $action): self
    {
        $procedure->loadMissing('auctionSetting');

        $actionLabel = match ($action) {
            'start' => 'Старт',
            'pause' => 'Пауза',
            'resume' => 'Продолжение',
            'finish' => 'Финиш',
            default => $action,
        };

        return new self(
            $procedure->id,
            $procedure->status->value,
            $procedure->status->label(),
            (bool) $procedure->auctionSetting?->is_paused,
            $procedure->auctionTradeStatus()?->value,
            $procedure->auctionTradeStatusLabel(),
            $action,
            $actionLabel,
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
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'AuctionStateChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'is_paused' => $this->isPaused,
            'auction_trade_status' => $this->auctionTradeStatus,
            'auction_trade_status_label' => $this->auctionTradeStatusLabel,
            'action' => $this->action,
            'action_label' => $this->actionLabel,
        ];
    }
}
