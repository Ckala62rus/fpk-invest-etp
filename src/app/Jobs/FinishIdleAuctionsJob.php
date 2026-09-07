<?php

namespace App\Jobs;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Services\AuctionSessionService;
use App\Services\AuctionTimerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Автозавершение аукционов по простою или истечении ends_at (фаза 8.5).
 *
 * Планировщик: каждую минуту.
 */
class FinishIdleAuctionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param AuctionTimerService $timer Правила простоя и дедлайна
     * @param AuctionSessionService $sessions Завершение торгов
     * @return void
     */
    public function handle(
        AuctionTimerService $timer,
        AuctionSessionService $sessions,
    ): void {
        $procedures = Procedure::query()
            ->with('auctionSetting')
            ->where('type', ProcedureType::Auction)
            ->where('status', ProcedureStatus::InProgress)
            ->get();

        foreach ($procedures as $procedure) {
            if (! $timer->shouldAutoFinish($procedure)) {
                continue;
            }

            $settings = $procedure->auctionSetting;
            $event = 'auction_finished_idle';
            $message = 'Аукцион завершён автоматически из-за отсутствия ставок.';

            if ($settings !== null && ! $timer->isIdleTimedOut($procedure, $settings)
                && $timer->isDeadlinePassed($procedure)
            ) {
                $event = 'auction_finished_deadline';
                $message = 'Аукцион завершён автоматически по истечении срока.';
            }

            try {
                $sessions->finishAutomatically($procedure, $event, $message);
            } catch (DomainException) {
                continue;
            }
        }
    }
}
