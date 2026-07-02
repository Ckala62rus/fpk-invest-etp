<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    case Invited = 'invited';
    case Admitted = 'admitted';
    case Rejected = 'rejected';
    case Winner = 'winner';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Приглашён',
            self::Admitted => 'Допущен',
            self::Rejected => 'Отклонён',
            self::Winner => 'Победитель',
        };
    }
}
