<?php

namespace App\Actions\Auction;

use App\Enums\AuctionMode;
use App\Enums\ProcedureStatus;
use App\Events\BidCancelled;
use App\Exceptions\DomainException;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Отмена ставки администратором (фаза 8.6).
 *
 * Ставка не удаляется: is_cancelled + cancelled_at + причина и аудит.
 * После отмены пересчитывается current_price лота.
 */
class CancelBidAction
{
    /**
     * Отменяет ставку и уведомляет участника (событие BidCancelled).
     *
     * @param Procedure $procedure Аукцион
     * @param AuctionBid $bid Ставка
     * @param User $admin Администратор
     * @param string $reason Причина отмены
     * @return AuctionBid
     *
     * @throws DomainException
     */
    public function execute(
        Procedure $procedure,
        AuctionBid $bid,
        User $admin,
        string $reason,
    ): AuctionBid {
        if ((int) $bid->procedure_id !== (int) $procedure->id) {
            throw new DomainException(
                message: 'Ставка не принадлежит этой процедуре.',
                statusCode: 422,
            );
        }

        if ($bid->is_cancelled) {
            throw new DomainException(
                message: 'Ставка уже отменена.',
                statusCode: 422,
            );
        }

        if ($procedure->status !== ProcedureStatus::InProgress) {
            throw new DomainException(
                message: 'Отменять ставки можно только пока аукцион в процессе.',
                statusCode: 422,
            );
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException(
                message: 'Укажите причину отмены ставки.',
                statusCode: 422,
            );
        }

        $cancelled = DB::transaction(function () use ($procedure, $bid, $admin, $reason): AuctionBid {
            /** @var AuctionBid $lockedBid */
            $lockedBid = AuctionBid::query()
                ->whereKey($bid->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBid->is_cancelled) {
                throw new DomainException(
                    message: 'Ставка уже отменена.',
                    statusCode: 422,
                );
            }

            $lockedBid->update([
                'is_cancelled' => true,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $lot = ProcedureLot::query()
                ->whereKey($lockedBid->lot_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lot->update([
                'current_price' => $this->recalculateCurrentPrice($procedure, $lot),
            ]);

            activity('auction_bid')
                ->causedBy($admin)
                ->performedOn($lockedBid)
                ->event('cancelled')
                ->withProperties([
                    'reason' => $reason,
                    'amount' => $lockedBid->amount,
                    'lot_id' => $lockedBid->lot_id,
                ])
                ->log('Ставка отменена администратором');

            return $lockedBid->refresh()->load('lot');
        });

        event(new BidCancelled($cancelled));

        return $cancelled;
    }

    /**
     * Лучшая неотменённая ставка или стартовая цена лота.
     *
     * @param Procedure $procedure Аукцион (направление торгов)
     * @param ProcedureLot $lot Лот
     * @return string
     */
    private function recalculateCurrentPrice(Procedure $procedure, ProcedureLot $lot): string
    {
        $settings = $procedure->auctionSetting;
        $mode = $settings?->auction_mode ?? AuctionMode::Decrease;

        $query = AuctionBid::query()
            ->where('lot_id', $lot->id)
            ->where('is_cancelled', false);

        $best = $mode === AuctionMode::Increase
            ? $query->max('amount')
            : $query->min('amount');

        if ($best === null) {
            return (string) $lot->start_price;
        }

        return (string) $best;
    }
}
