<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает согласования',
            self::Approved => 'Согласовано',
            self::Rejected => 'Отклонено',
        };
    }
}
