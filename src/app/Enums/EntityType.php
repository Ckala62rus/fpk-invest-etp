<?php

namespace App\Enums;

enum EntityType: string
{
    case Legal = 'legal';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Legal => 'Юридическое лицо',
            self::Individual => 'Физическое лицо',
        };
    }
}
