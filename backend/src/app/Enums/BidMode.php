<?php

namespace App\Enums;

enum BidMode: string
{
    case Standard = 'standard';
    case StepMinimum = 'step_minimum';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Стандартная ставка',
            self::StepMinimum => 'Ставка не меньше шага',
        };
    }
}
