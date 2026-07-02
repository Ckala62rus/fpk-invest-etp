<?php

namespace App\Enums;

enum ProcedureType: string
{
    case RequestForProposal = 'request_for_proposal';
    case Auction = 'auction';

    public function label(): string
    {
        return match ($this) {
            self::RequestForProposal => 'Запрос предложений',
            self::Auction => 'Электронный аукцион',
        };
    }
}
