<?php

namespace App\Enums;

enum AuctionMode: string
{
    case Decrease = 'decrease';
    case Increase = 'increase';

    public function label(): string
    {
        return match ($this) {
            self::Decrease => 'Аукцион на понижение',
            self::Increase => 'Аукцион в плюс (на повышение)',
        };
    }
}
