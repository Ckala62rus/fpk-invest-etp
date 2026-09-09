<?php

namespace App\Actions\Auction;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureVisibility;
use App\Enums\ProposalStatus;
use App\Events\AuctionExtended;
use App\Events\BidPlaced;
use App\Exceptions\DomainException;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AuctionSessionService;
use App\Services\AuctionTimerService;
use Illuminate\Support\Facades\DB;

/**
 * Подача ставки участником на лот аукциона (фаза 8.3).
 *
 * Проверяет статус торгов, шаг, направление (понижение/повышение),
 * запрет равных ставок и допуск участника.
 */
class PlaceBidAction
{
    /**
     * @param AuctionSessionService $sessions Проверка, что торги принимают ставки
     * @param AuctionTimerService $timer Автопродление ends_at (фаза 8.4)
     * @return void
     */
    public function __construct(
        private readonly AuctionSessionService $sessions,
        private readonly AuctionTimerService $timer,
    ) {
    }

    /**
     * Создаёт ставку и обновляет current_price лота.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @param ProcedureLot $lot Лот
     * @param User $participant Участник
     * @param string $amount Сумма ставки
     * @param string|null $ipAddress IP при подаче
     * @return AuctionBid
     *
     * @throws DomainException
     */
    public function execute(
        Procedure $procedure,
        ProcedureLot $lot,
        User $participant,
        string $amount,
        ?string $ipAddress = null,
    ): AuctionBid {
        if ((int) $lot->procedure_id !== (int) $procedure->id) {
            throw new DomainException(
                message: 'Лот не принадлежит этой процедуре.',
                statusCode: 422,
            );
        }

        if (! $this->sessions->isAcceptingBids($procedure)) {
            throw new DomainException(
                message: 'Сейчас нельзя подавать ставки по этому аукциону.',
                statusCode: 422,
            );
        }

        $this->assertParticipantAllowed($procedure, $participant);

        $extendedUntil = null;

        $bid = DB::transaction(function () use ($procedure, $lot, $participant, $amount, $ipAddress, &$extendedUntil): AuctionBid {
            $lockedProcedure = Procedure::query()
                ->whereKey($procedure->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var ProcedureLot $lockedLot */
            $lockedLot = ProcedureLot::query()
                ->whereKey($lot->id)
                ->lockForUpdate()
                ->firstOrFail();

            $settings = $lockedProcedure->auctionSetting()->lockForUpdate()->first();

            if ($settings === null) {
                throw new DomainException(
                    message: 'У процедуры отсутствуют настройки аукциона.',
                    statusCode: 422,
                );
            }

            $bestPrice = $lockedLot->current_price ?? $lockedLot->start_price;
            $this->assertAmountValid(
                amount: $amount,
                bestPrice: (string) $bestPrice,
                bidStep: (string) $lockedLot->bid_step,
                auctionMode: $settings->auction_mode,
                bidMode: $settings->bid_mode,
            );

            if ($settings->forbid_equal_bids) {
                $existsEqual = AuctionBid::query()
                    ->where('lot_id', $lockedLot->id)
                    ->where('is_cancelled', false)
                    ->where('amount', $amount)
                    ->exists();

                if ($existsEqual) {
                    throw new DomainException(
                        message: 'Ставка с такой суммой уже есть. Одинаковые ставки запрещены.',
                        statusCode: 422,
                    );
                }
            }

            $bid = AuctionBid::query()->create([
                'procedure_id' => $lockedProcedure->id,
                'lot_id' => $lockedLot->id,
                'user_id' => $participant->id,
                'amount' => $amount,
                'is_cancelled' => false,
                'ip_address' => $ipAddress,
            ]);

            $lockedLot->update(['current_price' => $amount]);

            $extendedUntil = $this->timer->extendIfNeeded($lockedProcedure, $settings);

            activity('auction_bid')
                ->causedBy($participant)
                ->performedOn($bid)
                ->event('placed')
                ->withProperties([
                    'procedure_id' => $lockedProcedure->id,
                    'lot_id' => $lockedLot->id,
                    'amount' => $amount,
                ])
                ->log('Подана ставка на аукционе');

            return $bid->fresh(['lot', 'procedure']) ?? $bid;
        });

        $procedureFresh = $bid->procedure ?? $procedure->fresh(['auctionSetting']);
        $procedureFresh?->loadMissing('auctionSetting');

        // WebSocket-тикер без ФИО/email автора ставки (фаза 8.9)
        event(BidPlaced::fromBid($bid, $procedureFresh ?? $procedure));

        if ($extendedUntil !== null) {
            event(new AuctionExtended(
                $bid->procedure_id,
                $extendedUntil->toIso8601String(),
                (int) ($procedureFresh?->auctionSetting?->extension_minutes ?? 5),
            ));
        }

        return $bid;
    }

    /**
     * @param Procedure $procedure Аукцион
     * @param User $participant Участник
     * @return void
     *
     * @throws DomainException
     */
    private function assertParticipantAllowed(Procedure $procedure, User $participant): void
    {
        if (! $participant->hasRole('participant')) {
            throw new DomainException(
                message: 'Ставки может подавать только участник.',
                statusCode: 403,
            );
        }

        $settings = $procedure->auctionSetting;

        if ($settings?->only_admitted_from_rfp && $procedure->source_procedure_id !== null) {
            $admittedOnRfp = Proposal::query()
                ->where('procedure_id', $procedure->source_procedure_id)
                ->where('user_id', $participant->id)
                ->where('status', ProposalStatus::Admitted)
                ->exists();

            if (! $admittedOnRfp) {
                throw new DomainException(
                    message: 'К торгам допущены только участники, прошедшие 1-й этап (КП).',
                    statusCode: 403,
                );
            }

            return;
        }

        if ($procedure->visibility === ProcedureVisibility::Closed) {
            $isParticipant = $procedure->participants()
                ->where('user_id', $participant->id)
                ->whereIn('status', [
                    ParticipantStatus::Invited,
                    ParticipantStatus::Admitted,
                ])
                ->exists();

            if (! $isParticipant) {
                throw new DomainException(
                    message: 'Вы не приглашены к участию в этом аукционе.',
                    statusCode: 403,
                );
            }
        }
    }

    /**
     * @param string $amount Новая ставка
     * @param string $bestPrice Текущая лучшая / стартовая
     * @param string $bidStep Шаг лота
     * @param AuctionMode $auctionMode Направление
     * @param BidMode $bidMode Режим шага
     * @return void
     *
     * @throws DomainException
     */
    private function assertAmountValid(
        string $amount,
        string $bestPrice,
        string $bidStep,
        AuctionMode $auctionMode,
        BidMode $bidMode,
    ): void {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new DomainException(
                message: 'Сумма ставки должна быть больше нуля.',
                statusCode: 422,
            );
        }

        if ($auctionMode === AuctionMode::Decrease) {
            if (bccomp($amount, $bestPrice, 2) >= 0) {
                throw new DomainException(
                    message: 'В аукционе на понижение ставка должна быть меньше текущей цены.',
                    statusCode: 422,
                );
            }

            $diff = bcsub($bestPrice, $amount, 2);
        } else {
            if (bccomp($amount, $bestPrice, 2) <= 0) {
                throw new DomainException(
                    message: 'В аукционе на повышение ставка должна быть больше текущей цены.',
                    statusCode: 422,
                );
            }

            $diff = bcsub($amount, $bestPrice, 2);
        }

        if ($bidMode === BidMode::StepMinimum && bccomp($diff, $bidStep, 2) < 0) {
            throw new DomainException(
                message: 'Изменение ставки должно быть не меньше шага лота ('.$bidStep.').',
                statusCode: 422,
            );
        }
    }
}
