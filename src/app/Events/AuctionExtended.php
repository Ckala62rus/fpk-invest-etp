<?php

namespace App\Events;

use App\Models\Procedure;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Срок аукциона продлён из-за ставки (фаза 8.9).
 */
class AuctionExtended implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param int $procedureId ID аукциона
     * @param string $endsAt Новое время окончания
     * @param int $extensionMinutes На сколько минут продлили
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public string $endsAt,
        public int $extensionMinutes,
    ) {
    }

    /**
     * @param Procedure $procedure Процедура
     * @param int $extensionMinutes Минуты
     * @return self
     */
    public static function fromProcedure(Procedure $procedure, int $extensionMinutes): self
    {
        return new self(
            $procedure->id,
            $procedure->ends_at?->toIso8601String() ?? '',
            $extensionMinutes,
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
        return 'AuctionExtended';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ends_at' => $this->endsAt,
            'extension_minutes' => $this->extensionMinutes,
        ];
    }
}
