<?php

namespace App\Services;

use App\Contracts\FpkExportClientInterface;
use App\Models\Procedure;
use Illuminate\Support\Facades\Log;

/**
 * Заглушка экспорта на fpkinvest.ru до появления API (фаза 6.9).
 */
class StubFpkExportClient implements FpkExportClientInterface
{
    /**
     * @param Procedure $procedure Опубликованная ТЗП
     * @return void
     */
    public function exportProcedure(Procedure $procedure): void
    {
        Log::info('FPK export stub: procedure queued for external sync', [
            'procedure_id' => $procedure->id,
            'number' => $procedure->number,
            'title' => $procedure->title,
        ]);
    }
}
