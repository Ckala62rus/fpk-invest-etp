<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case Event = 'event';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'По событию',
            self::Scheduled => 'По расписанию',
        };
    }
}
