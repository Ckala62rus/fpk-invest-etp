<?php

namespace App\Enums;

enum AdmissionDecision: string
{
    case Admit = 'admit';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::Admit => 'Допуск',
            self::Reject => 'Недопуск',
        };
    }
}
