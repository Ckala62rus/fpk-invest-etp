<?php

namespace App\Enums;

enum WinnerMode: string
{
    case PerLot = 'per_lot';
    case TotalSum = 'total_sum';

    public function label(): string
    {
        return match ($this) {
            self::PerLot => 'Победитель по каждому лоту',
            self::TotalSum => 'Победитель по сумме лотов',
        };
    }
}
