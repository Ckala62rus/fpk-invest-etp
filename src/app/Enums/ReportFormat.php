<?php

namespace App\Enums;

/**
 * Формат файла выгрузки отчёта (фаза 10).
 */
enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Xlsx = 'xlsx';
    case Doc = 'doc';

    /**
     * Подпись формата для админки.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Xlsx => 'Excel',
            self::Doc => 'Word',
        };
    }
}
