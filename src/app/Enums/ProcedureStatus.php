<?php

namespace App\Enums;

enum ProcedureStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Accepting = 'accepting';
    case Review = 'review';
    case AuctionPending = 'auction_pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Published => 'Опубликована',
            self::Accepting => 'Приём заявок',
            self::Review => 'Рассмотрение',
            self::AuctionPending => 'Ожидает аукциона',
            self::InProgress => 'В процессе',
            self::Completed => 'Завершена',
            self::Cancelled => 'Отменена',
        };
    }
}
