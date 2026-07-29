<?php

namespace App\Actions\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\WinnerMode;
use App\Exceptions\DomainException;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use Illuminate\Support\Facades\DB;

/**
 * Обновляет черновик ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.1: редактирование только в статусе draft.
 * Смена типа на auction создаёт auction_settings при отсутствии.
 */
class UpdateProcedureAction
{
    /**
     * Обновляет поля черновика процедуры.
     *
     * @param Procedure $procedure Целевая процедура
     * @param array<string, mixed> $data Валидированные поля (частичное обновление)
     * @return Procedure Обновлённая процедура со связями
     *
     * @throws DomainException Если процедура не в статусе draft
     */
    public function execute(Procedure $procedure, array $data): Procedure
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Редактировать можно только черновик процедуры.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($procedure, $data): Procedure {
            $procedure->update($data);
            $procedure->refresh();

            // При смене типа на аукцион — создать настройки, если их ещё нет
            if (
                $procedure->type === ProcedureType::Auction
                && ! $procedure->auctionSetting()->exists()
            ) {
                AuctionSetting::query()->firstOrCreate(
                    ['procedure_id' => $procedure->id],
                    [
                        'bid_mode' => BidMode::Standard,
                        'auction_mode' => AuctionMode::Decrease,
                        'extension_minutes' => 5,
                        'extension_trigger_minutes' => null,
                        'idle_timeout_minutes' => 30,
                        'forbid_equal_bids' => true,
                        'winner_mode' => WinnerMode::PerLot,
                        'only_admitted_from_rfp' => false,
                    ],
                );
            }

            return $procedure->load([
                'company',
                'category',
                'responsibleUser',
                'creator',
                'auctionSetting',
            ]);
        });
    }
}
