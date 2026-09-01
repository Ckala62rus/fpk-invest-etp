<?php

namespace App\Contracts;

use App\Models\Procedure;

/**
 * Клиент экспорта опубликованной процедуры на fpkinvest.ru (фаза 6.9).
 */
interface FpkExportClientInterface
{
    /**
     * Экспортирует процедуру на внешний портал закупок.
     *
     * @param Procedure $procedure Опубликованная ТЗП
     * @return void
     */
    public function exportProcedure(Procedure $procedure): void;
}
