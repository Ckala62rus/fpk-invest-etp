<?php

namespace App\Enums;

enum ProcedureVisibility: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Открытая',
            self::Closed => 'Закрытая (только приглашённые)',
        };
    }
}
