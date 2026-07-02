<?php

namespace App\Enums;

enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Xlsx = 'xlsx';
    case Doc = 'doc';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Xlsx => 'Excel',
            self::Doc => 'Word',
        };
    }
}
