<?php

namespace App\Enums;

enum PasswordResetAdminStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает обработки',
            self::Resolved => 'Обработан',
            self::Rejected => 'Отклонён',
        };
    }
}
