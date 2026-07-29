<?php

namespace App\Services;

use App\Models\Procedure;

/**
 * Сервис оркестрации ТЗП (торгово-закупочных процедур) ЭТП.
 *
 * Фаза 5.1: генерация уникального номера черновика; дальнейшие сценарии
 * (публикация, рассылки) будут добавляться сюда как оркестрация Actions.
 */
class ProcedureService
{
    /**
     * Генерирует уникальный номер процедуры формата TZP-YYYYMMDD-NNNN.
     *
     * Счётчик берётся по числу процедур, созданных за текущий день (включая soft-deleted),
     * с повторными попытками при гонке уникального индекса.
     *
     * @return string Уникальный номер ТЗП
     */
    public function generateNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'TZP-'.$date.'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $seq = Procedure::withTrashed()
                ->where('number', 'like', $prefix.'%')
                ->count() + 1 + $attempt;

            $number = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            $exists = Procedure::withTrashed()->where('number', $number)->exists();

            if (! $exists) {
                return $number;
            }
        }

        // Крайний случай гонки: суффикс по микровремени
        return $prefix.str_pad((string) (now()->micro % 10000), 4, '0', STR_PAD_LEFT);
    }
}
