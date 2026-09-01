<?php

namespace App\Actions\Admin;

use App\Contracts\FpkExportClientInterface;
use App\Models\Procedure;

/**
 * Экспорт опубликованной ТЗП на fpkinvest.ru (фаза 6.9).
 */
class ExportProcedureToFpkAction
{
    /**
     * @param FpkExportClientInterface $client Внешний клиент экспорта
     * @return void
     */
    public function __construct(
        private readonly FpkExportClientInterface $client,
    ) {
    }

    /**
     * Отправляет процедуру во внешний каталог закупок.
     *
     * @param Procedure $procedure Опубликованная ТЗП
     * @return void
     */
    public function execute(Procedure $procedure): void
    {
        $this->client->exportProcedure($procedure);
    }
}
