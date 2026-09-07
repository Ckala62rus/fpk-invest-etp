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
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Обновление настроек электронного аукциона (фаза 8.1).
 *
 * Доступно для черновика и auction_pending; после старта торгов — запрещено.
 */
class UpdateAuctionSettingAction
{
    /**
     * Обновляет настройки аукциона; при отсутствии создаёт запись с дефолтами + переданные поля.
     *
     * @param Procedure $procedure Процедура типа auction
     * @param array<string, mixed> $data Валидированные поля
     * @param User $actor Администратор, выполняющий изменение
     * @return AuctionSetting Обновлённые настройки
     *
     * @throws DomainException Если тип/статус не позволяют менять настройки
     */
    public function execute(Procedure $procedure, array $data, User $actor): AuctionSetting
    {
        $this->assertCanUpdate($procedure);

        return DB::transaction(function () use ($procedure, $data, $actor): AuctionSetting {
            $settings = $procedure->auctionSetting;

            if ($settings === null) {
                $settings = AuctionSetting::query()->create(array_merge([
                    'procedure_id' => $procedure->id,
                    'bid_mode' => BidMode::Standard,
                    'auction_mode' => AuctionMode::Decrease,
                    'extension_minutes' => 5,
                    'extension_trigger_minutes' => null,
                    'idle_timeout_minutes' => 30,
                    'forbid_equal_bids' => true,
                    'winner_mode' => WinnerMode::PerLot,
                    'only_admitted_from_rfp' => false,
                ], $data));
            } else {
                $settings->update($data);
                $settings = $settings->refresh();
            }

            activity('procedure')
                ->causedBy($actor)
                ->performedOn($procedure)
                ->event('auction_settings_updated')
                ->withProperties([
                    'auction_setting_id' => $settings->id,
                    'changes' => $data,
                ])
                ->log('Обновлены настройки аукциона');

            return $settings;
        });
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanUpdate(Procedure $procedure): void
    {
        if ($procedure->type !== ProcedureType::Auction) {
            throw new DomainException(
                message: 'Настройки аукциона доступны только для процедур типа auction.',
                statusCode: 422,
            );
        }

        $editable = in_array($procedure->status, [
            ProcedureStatus::Draft,
            ProcedureStatus::AuctionPending,
        ], true);

        if (! $editable) {
            throw new DomainException(
                message: 'Настройки аукциона можно менять только до начала торгов (черновик или ожидание аукциона).',
                statusCode: 422,
            );
        }
    }
}
