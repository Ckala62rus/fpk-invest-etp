<?php

namespace App\Enums;

enum TradeDirection: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Закупка',
            self::Sale => 'Продажа',
        };
    }
}
