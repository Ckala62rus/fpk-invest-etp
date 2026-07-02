<?php

namespace App\Enums;

enum CustomFieldScope: string
{
    case Procedure = 'procedure';
    case Participant = 'participant';
    case Lot = 'lot';

    public function label(): string
    {
        return match ($this) {
            self::Procedure => 'Поле процедуры',
            self::Participant => 'Поле участника',
            self::Lot => 'Поле лота',
        };
    }
}
