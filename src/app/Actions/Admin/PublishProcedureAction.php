<?php

namespace App\Actions\Admin;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Events\ProcedurePublished;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Публикует черновик ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.7: draft → published (+ published_at), затем событие → Job email-рассылки.
 */
class PublishProcedureAction
{
    /**
     * Публикует процедуру от имени администратора.
     *
     * @param Procedure $procedure Черновик ТЗП
     * @param User $publisher Администратор, выполняющий публикацию
     * @return Procedure Опубликованная процедура
     *
     * @throws DomainException Если нельзя опубликовать
     */
    public function execute(Procedure $procedure, User $publisher): Procedure
    {
        $this->assertCanPublish($procedure);

        return DB::transaction(function () use ($procedure, $publisher): Procedure {
            $nextStatus = $procedure->type === ProcedureType::Auction
                ? ProcedureStatus::AuctionPending
                : ProcedureStatus::Accepting;

            $procedure->update([
                'status' => $nextStatus,
                'published_at' => now(),
            ]);

            activity('procedure')
                ->causedBy($publisher)
                ->performedOn($procedure)
                ->event('published')
                ->withProperties([
                    'number' => $procedure->number,
                    'status' => $nextStatus->value,
                ])
                ->log('Процедура опубликована');

            $procedure = $procedure->fresh([
                'company',
                'category',
                'responsibleUser',
                'creator',
                'auctionSetting',
            ]);

            ProcedurePublished::dispatch($procedure);

            return $procedure;
        });
    }

    /**
     * Проверяет бизнес-правила публикации.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanPublish(Procedure $procedure): void
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Опубликовать можно только черновик процедуры.',
                statusCode: 422,
            );
        }

        if (trim($procedure->title) === '') {
            throw new DomainException(
                message: 'Укажите название процедуры перед публикацией.',
                statusCode: 422,
            );
        }

        if ($procedure->ends_at === null) {
            throw new DomainException(
                message: 'Укажите дату окончания перед публикацией.',
                statusCode: 422,
            );
        }

        if ($procedure->type === ProcedureType::Auction) {
            if (! $procedure->auctionSetting()->exists()) {
                throw new DomainException(
                    message: 'Для аукциона необходимо заполнить настройки торгов.',
                    statusCode: 422,
                );
            }

            if ($procedure->lots()->count() < 1) {
                throw new DomainException(
                    message: 'Для аукциона добавьте хотя бы один лот.',
                    statusCode: 422,
                );
            }
        }

        if (
            $procedure->visibility === ProcedureVisibility::Closed
            && $procedure->participants()->count() < 1
        ) {
            throw new DomainException(
                message: 'Для закрытой процедуры пригласите хотя бы одного участника.',
                statusCode: 422,
            );
        }
    }
}
