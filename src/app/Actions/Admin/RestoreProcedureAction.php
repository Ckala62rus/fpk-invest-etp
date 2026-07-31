<?php

namespace App\Actions\Admin;

use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Восстанавливает ТЗП из папки «удалённые».
 *
 * Фаза 5.8: restore soft-deleted процедуры.
 */
class RestoreProcedureAction
{
    /**
     * Восстанавливает мягко удалённую процедуру.
     *
     * @param Procedure $procedure Удалённая ТЗП (withTrashed)
     * @param User $restorer Кто восстанавливает
     * @return Procedure Восстановленная процедура
     *
     * @throws DomainException Если процедура не удалена
     */
    public function execute(Procedure $procedure, User $restorer): Procedure
    {
        if (! $procedure->trashed()) {
            throw new DomainException(
                message: 'Процедура не находится в удалённых.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($procedure, $restorer): Procedure {
            $procedure->restore();
            $procedure->update(['deleted_by' => null]);

            activity('procedure')
                ->causedBy($restorer)
                ->performedOn($procedure)
                ->event('restored')
                ->withProperties(['number' => $procedure->number])
                ->log('Процедура восстановлена из удалённых');

            return $procedure->fresh([
                'company',
                'category',
                'responsibleUser',
                'creator',
                'auctionSetting',
            ]);
        });
    }
}
