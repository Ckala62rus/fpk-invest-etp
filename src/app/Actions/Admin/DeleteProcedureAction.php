<?php

namespace App\Actions\Admin;

use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Мягко удаляет ТЗП (торгово-закупочную процедуру) в папку «удалённые».
 *
 * Фаза 5.8: soft delete + deleted_by; данные остаются в БД.
 */
class DeleteProcedureAction
{
    /**
     * Выполняет soft delete процедуры.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @param User $deleter Кто удаляет
     * @return void
     *
     * @throws DomainException Если процедура уже удалена
     */
    public function execute(Procedure $procedure, User $deleter): void
    {
        if ($procedure->trashed()) {
            throw new DomainException(
                message: 'Процедура уже удалена.',
                statusCode: 422,
            );
        }

        DB::transaction(function () use ($procedure, $deleter): void {
            $procedure->update(['deleted_by' => $deleter->id]);
            $procedure->delete();

            activity('procedure')
                ->causedBy($deleter)
                ->performedOn($procedure)
                ->event('deleted')
                ->withProperties(['number' => $procedure->number])
                ->log('Процедура перемещена в удалённые');
        });
    }
}
