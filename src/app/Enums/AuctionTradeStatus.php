<?php

namespace App\Enums;

/**
 * Фаза торгов аукциона для UI (пауза — отдельно от статуса ТЗП in_progress).
 *
 * Статус процедуры при паузе остаётся «в процессе»; признак паузы — auction_settings.is_paused.
 */
enum AuctionTradeStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * Подпись для админки и кабинета участника.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает старта',
            self::Running => 'Идут торги',
            self::Paused => 'На паузе',
            self::Finished => 'Завершён',
            self::Cancelled => 'Отменён',
        };
    }
}
