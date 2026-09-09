<?php

namespace App\Services;

use App\Actions\Auction\DetermineWinnersAction;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Events\AuctionStateChanged;
use App\Exceptions\DomainException;
use App\Jobs\GenerateAuctionProtocolJob;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Жизненный цикл торгов аукциона: start / pause / resume / finish (фаза 8.2).
 *
 * После финиша назначает победителей (фаза 8.10) и шлёт AuctionStateChanged (фаза 8.9).
 * Не путать с моделью AuctionSession (presence онлайн-участников, фаза 8.8).
 */
class AuctionSessionService
{
    /**
     * @param DetermineWinnersAction $winners Назначение победителей при завершении
     * @return void
     */
    public function __construct(
        private readonly DetermineWinnersAction $winners,
    ) {
    }
    /**
     * Запускает торги: auction_pending → in_progress.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @param User $actor Администратор
     * @return Procedure
     *
     * @throws DomainException
     */
    public function start(Procedure $procedure, User $actor): Procedure
    {
        $this->assertAuction($procedure);

        if ($procedure->status !== ProcedureStatus::AuctionPending) {
            throw new DomainException(
                message: 'Запустить торги можно только из статуса «ожидает аукциона».',
                statusCode: 422,
            );
        }

        $settings = $this->requireSettings($procedure);

        if ($procedure->lots()->count() < 1) {
            throw new DomainException(
                message: 'Нельзя запустить аукцион без лотов.',
                statusCode: 422,
            );
        }

        $started = DB::transaction(function () use ($procedure, $actor, $settings): Procedure {
            $procedure->update([
                'status' => ProcedureStatus::InProgress,
                'starts_at' => $procedure->starts_at ?? now(),
            ]);

            $settings->update([
                'is_paused' => false,
                'paused_at' => null,
            ]);

            activity('procedure')
                ->causedBy($actor)
                ->performedOn($procedure)
                ->event('auction_started')
                ->log('Аукцион запущен');

            return $procedure->fresh(['auctionSetting', 'lots']) ?? $procedure;
        });

        event(AuctionStateChanged::fromProcedure($started, 'start'));

        return $started;
    }

    /**
     * Ставит торги на паузу (ставки не принимаются до resume).
     *
     * @param Procedure $procedure Процедура-аукцион
     * @param User $actor Администратор
     * @return Procedure
     *
     * @throws DomainException
     */
    public function pause(Procedure $procedure, User $actor): Procedure
    {
        $this->assertAuction($procedure);
        $this->assertInProgress($procedure);

        $settings = $this->requireSettings($procedure);

        if ($settings->is_paused) {
            throw new DomainException(
                message: 'Торги уже на паузе.',
                statusCode: 422,
            );
        }

        $paused = DB::transaction(function () use ($procedure, $actor, $settings): Procedure {
            $settings->update([
                'is_paused' => true,
                'paused_at' => now(),
            ]);

            activity('procedure')
                ->causedBy($actor)
                ->performedOn($procedure)
                ->event('auction_paused')
                ->log('Аукцион поставлен на паузу');

            return $procedure->fresh(['auctionSetting']) ?? $procedure;
        });

        event(AuctionStateChanged::fromProcedure($paused, 'pause'));

        return $paused;
    }

    /**
     * Снимает паузу и продолжает торги.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @param User $actor Администратор
     * @return Procedure
     *
     * @throws DomainException
     */
    public function resume(Procedure $procedure, User $actor): Procedure
    {
        $this->assertAuction($procedure);
        $this->assertInProgress($procedure);

        $settings = $this->requireSettings($procedure);

        if (! $settings->is_paused) {
            throw new DomainException(
                message: 'Торги не на паузе.',
                statusCode: 422,
            );
        }

        $resumed = DB::transaction(function () use ($procedure, $actor, $settings): Procedure {
            $settings->update([
                'is_paused' => false,
                'paused_at' => null,
            ]);

            activity('procedure')
                ->causedBy($actor)
                ->performedOn($procedure)
                ->event('auction_resumed')
                ->log('Аукцион возобновлён');

            return $procedure->fresh(['auctionSetting']) ?? $procedure;
        });

        event(AuctionStateChanged::fromProcedure($resumed, 'resume'));

        return $resumed;
    }

    /**
     * Завершает торги: in_progress → completed.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @param User $actor Администратор
     * @return Procedure
     *
     * @throws DomainException
     */
    public function finish(Procedure $procedure, User $actor): Procedure
    {
        $this->assertAuction($procedure);
        $this->assertInProgress($procedure);

        $settings = $this->requireSettings($procedure);

        $finished = DB::transaction(function () use ($procedure, $actor, $settings): Procedure {
            $procedure->update([
                'status' => ProcedureStatus::Completed,
                'completed_at' => now(),
            ]);

            $settings->update([
                'is_paused' => false,
                'paused_at' => null,
            ]);

            activity('procedure')
                ->causedBy($actor)
                ->performedOn($procedure)
                ->event('auction_finished')
                ->log('Аукцион завершён');

            $fresh = $procedure->fresh(['auctionSetting', 'lots']) ?? $procedure;
            $this->winners->execute($fresh);

            return $fresh->fresh(['auctionSetting', 'lots']) ?? $fresh;
        });

        event(AuctionStateChanged::fromProcedure($finished, 'finish'));
        GenerateAuctionProtocolJob::dispatch($finished->id, $actor->id);

        return $finished;
    }

    /**
     * Завершает торги без администратора (простой или истечение ends_at, фаза 8.5).
     *
     * @param Procedure $procedure Аукцион in_progress
     * @param string $event Код события аудита
     * @param string $logMessage Текст журнала
     * @return Procedure
     *
     * @throws DomainException
     */
    public function finishAutomatically(
        Procedure $procedure,
        string $event = 'auction_finished_idle',
        string $logMessage = 'Аукцион завершён автоматически',
    ): Procedure {
        $this->assertAuction($procedure);
        $this->assertInProgress($procedure);

        $settings = $this->requireSettings($procedure);

        $finished = DB::transaction(function () use ($procedure, $settings, $event, $logMessage): Procedure {
            $procedure->update([
                'status' => ProcedureStatus::Completed,
                'completed_at' => now(),
            ]);

            $settings->update([
                'is_paused' => false,
                'paused_at' => null,
            ]);

            activity('procedure')
                ->performedOn($procedure)
                ->event($event)
                ->log($logMessage);

            $fresh = $procedure->fresh(['auctionSetting', 'lots']) ?? $procedure;
            $this->winners->execute($fresh);

            return $fresh->fresh(['auctionSetting', 'lots']) ?? $fresh;
        });

        event(AuctionStateChanged::fromProcedure($finished, 'finish'));
        GenerateAuctionProtocolJob::dispatch($finished->id, null);

        return $finished;
    }

    /**
     * Можно ли сейчас принимать ставки.
     *
     * @param Procedure $procedure Процедура
     * @return bool
     */
    public function isAcceptingBids(Procedure $procedure): bool
    {
        if ($procedure->type !== ProcedureType::Auction) {
            return false;
        }

        if ($procedure->status !== ProcedureStatus::InProgress) {
            return false;
        }

        if ($procedure->ends_at !== null && $procedure->ends_at->lte(now())) {
            return false;
        }

        $settings = $procedure->auctionSetting;

        return $settings !== null && ! $settings->is_paused;
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertAuction(Procedure $procedure): void
    {
        if ($procedure->type !== ProcedureType::Auction) {
            throw new DomainException(
                message: 'Операция доступна только для процедур типа auction.',
                statusCode: 422,
            );
        }
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertInProgress(Procedure $procedure): void
    {
        if ($procedure->status !== ProcedureStatus::InProgress) {
            throw new DomainException(
                message: 'Операция доступна только для аукциона в статусе «в процессе».',
                statusCode: 422,
            );
        }
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return AuctionSetting
     *
     * @throws DomainException
     */
    private function requireSettings(Procedure $procedure): AuctionSetting
    {
        $settings = $procedure->auctionSetting;

        if ($settings === null) {
            throw new DomainException(
                message: 'У процедуры отсутствуют настройки аукциона.',
                statusCode: 422,
            );
        }

        return $settings;
    }
}
