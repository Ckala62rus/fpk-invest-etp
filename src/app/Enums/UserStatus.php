<?php

namespace App\Enums;

enum UserStatus: string
{
    case PendingEmail = 'pending_email';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::PendingEmail => 'Ожидает подтверждения email',
            self::PendingApproval => 'Ожидает одобрения администратором',
            self::Active => 'Активен',
            self::Blocked => 'Заблокирован',
        };
    }
}
