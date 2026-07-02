<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Clarification = 'clarification';
    case Admitted = 'admitted';
    case Rejected = 'rejected';
    case Winner = 'winner';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Submitted => 'Подана',
            self::UnderReview => 'На рассмотрении',
            self::Clarification => 'Уточнение',
            self::Admitted => 'Допущена',
            self::Rejected => 'Отклонена',
            self::Winner => 'Победитель',
        };
    }
}
