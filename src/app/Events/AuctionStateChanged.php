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
     * @param string $status Статус процедуры
     * @param bool $isPaused Пауза
     * @param string $action start|pause|resume|finish
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public string $status,
        public bool $isPaused,
        public string $action,
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

        return new self(
            $procedure->id,
            $procedure->status->value,
            (bool) $procedure->auctionSetting?->is_paused,
            $action,
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
            'is_paused' => $this->isPaused,
            'action' => $this->action,
        ];
    }
}
