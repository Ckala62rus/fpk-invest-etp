<?php

namespace App\Actions\Auction;

use App\Enums\AuctionMode;
use App\Enums\ParticipantStatus;
use App\Enums\WinnerMode;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\ProcedureParticipant;

/**
 * Назначает победителей лотов после завершения аукциона (фаза 8.10).
 *
 * per_lot — лучшая неотменённая ставка на каждом лоте.
 * total_sum — один победитель с лучшей суммой лучших ставок по всем лотам
 * (нужны ставки на каждый лот); иначе запасной расчёт как per_lot.
 */
class DetermineWinnersAction
{
    /**
     * Записывает winner_user_id на лоты и статус winner у участников процедуры.
     *
     * @param Procedure $procedure Завершённый аукцион (лоты и настройки уже сохранены)
     * @return void
     */
    public function execute(Procedure $procedure): void
    {
        $procedure->loadMissing(['auctionSetting', 'lots']);

        $mode = $procedure->auctionSetting?->auction_mode ?? AuctionMode::Decrease;
        $winnerMode = $procedure->auctionSetting?->winner_mode ?? WinnerMode::PerLot;

        $lots = $procedure->lots;

        if ($lots->isEmpty()) {
            return;
        }

        $winnersByLot = $winnerMode === WinnerMode::TotalSum
            ? $this->winnersByTotalSum($procedure, $lots, $mode)
            : $this->winnersByLot($lots, $mode);

        foreach ($lots as $lot) {
            $lot->update([
                'winner_user_id' => $winnersByLot[$lot->id] ?? null,
            ]);
        }

        $winnerIds = array_values(array_unique(array_filter($winnersByLot)));

        $this->markParticipants($procedure, $winnerIds);
    }

    /**
     * Лучшая ставка по каждому лоту отдельно.
     *
     * @param \Illuminate\Support\Collection<int, ProcedureLot> $lots Лоты
     * @param AuctionMode $mode Понижение или повышение
     * @return array<int, int|null> lot_id => user_id
     */
    private function winnersByLot($lots, AuctionMode $mode): array
    {
        $map = [];

        foreach ($lots as $lot) {
            $map[$lot->id] = $this->bestBidUserId((int) $lot->id, $mode);
        }

        return $map;
    }

    /**
     * Один победитель на все лоты по сумме лучших ставок (нужны ставки на каждый лот).
     *
     * @param Procedure $procedure Аукцион
     * @param \Illuminate\Support\Collection<int, ProcedureLot> $lots Лоты
     * @param AuctionMode $mode Направление
     * @return array<int, int|null>
     */
    private function winnersByTotalSum(Procedure $procedure, $lots, AuctionMode $mode): array
    {
        $lotIds = $lots->pluck('id')->map(fn ($id) => (int) $id)->all();
        $userIds = AuctionBid::query()
            ->where('procedure_id', $procedure->id)
            ->where('is_cancelled', false)
            ->distinct()
            ->pluck('user_id');

        $bestSum = null;
        $winnerId = null;

        foreach ($userIds as $userId) {
            $sum = '0.00';
            $hasAllLots = true;

            foreach ($lotIds as $lotId) {
                $best = $this->bestAmountForUser((int) $lotId, (int) $userId, $mode);

                if ($best === null) {
                    $hasAllLots = false;
                    break;
                }

                $sum = bcadd($sum, $best, 2);
            }

            if (! $hasAllLots) {
                continue;
            }

            if ($bestSum === null || $this->isBetterAmount($sum, $bestSum, $mode)) {
                $bestSum = $sum;
                $winnerId = (int) $userId;
            }
        }

        if ($winnerId === null) {
            return $this->winnersByLot($lots, $mode);
        }

        $map = [];

        foreach ($lotIds as $lotId) {
            $map[$lotId] = $winnerId;
        }

        return $map;
    }

    /**
     * Автор лучшей неотменённой ставки по лоту (при равенстве — более ранняя ставка).
     *
     * @param int $lotId Лот
     * @param AuctionMode $mode Направление
     * @return int|null
     */
    private function bestBidUserId(int $lotId, AuctionMode $mode): ?int
    {
        $query = AuctionBid::query()
            ->where('lot_id', $lotId)
            ->where('is_cancelled', false);

        $bid = $mode === AuctionMode::Increase
            ? $query->orderByDesc('amount')->orderBy('id')->first()
            : $query->orderBy('amount')->orderBy('id')->first();

        return $bid?->user_id !== null ? (int) $bid->user_id : null;
    }

    /**
     * Лучшая сумма конкретного участника на лоте.
     *
     * @param int $lotId Лот
     * @param int $userId Участник
     * @param AuctionMode $mode Направление
     * @return string|null
     */
    private function bestAmountForUser(int $lotId, int $userId, AuctionMode $mode): ?string
    {
        $query = AuctionBid::query()
            ->where('lot_id', $lotId)
            ->where('user_id', $userId)
            ->where('is_cancelled', false);

        $amount = $mode === AuctionMode::Increase
            ? $query->max('amount')
            : $query->min('amount');

        return $amount !== null ? (string) $amount : null;
    }

    /**
     * @param string $candidate Сумма кандидата
     * @param string $current Текущий лучший
     * @param AuctionMode $mode Направление
     * @return bool
     */
    private function isBetterAmount(string $candidate, string $current, AuctionMode $mode): bool
    {
        $cmp = bccomp($candidate, $current, 2);

        return $mode === AuctionMode::Increase ? $cmp === 1 : $cmp === -1;
    }

    /**
     * Победители получают status=winner; прежних winner этой процедуры сбрасываем в admitted.
     *
     * @param Procedure $procedure Аукцион
     * @param list<int> $winnerIds ID пользователей-победителей
     * @return void
     */
    private function markParticipants(Procedure $procedure, array $winnerIds): void
    {
        ProcedureParticipant::query()
            ->where('procedure_id', $procedure->id)
            ->where('status', ParticipantStatus::Winner)
            ->update(['status' => ParticipantStatus::Admitted]);

        foreach ($winnerIds as $userId) {
            $row = ProcedureParticipant::query()->firstOrNew([
                'procedure_id' => $procedure->id,
                'user_id' => $userId,
            ]);

            if ($row->status === ParticipantStatus::Rejected) {
                continue;
            }

            $row->status = ParticipantStatus::Winner;
            $row->save();
        }
    }
}
