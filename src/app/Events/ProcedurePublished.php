<?php

namespace App\Events;

use App\Models\Procedure;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие публикации ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.7: после успешного PublishProcedureAction — слушатель ставит Job рассылки.
 */
class ProcedurePublished
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Опубликованная процедура.
     *
     * @var Procedure
     */
    public Procedure $procedure;

    /**
     * @param Procedure $procedure Опубликованная ТЗП
     * @return void
     */
    public function __construct(Procedure $procedure)
    {
        $this->procedure = $procedure;
    }
}
