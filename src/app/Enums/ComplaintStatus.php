<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::InProgress => 'В работе',
            self::Resolved => 'Решена',
            self::Rejected => 'Отклонена',
        };
    }
}
