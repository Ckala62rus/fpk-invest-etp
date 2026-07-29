<?php

namespace App\Actions\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\WinnerMode;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use App\Models\User;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\DB;

/**
 * Создаёт черновик ТЗП (торгово-закупочной процедуры).
 *
 * Для типа auction сразу создаёт auction_settings с значениями по умолчанию.
 */
class CreateProcedureAction
{
    /**
     * Сервис генерации номера ТЗП.
     *
     * @var ProcedureService
     */
    private readonly ProcedureService $procedures;

    /**
     * @param ProcedureService $procedures Сервис ТЗП
     * @return void
     */
    public function __construct(ProcedureService $procedures)
    {
        $this->procedures = $procedures;
    }

    /**
     * Создаёт черновик процедуры от имени администратора.
     *
     * @param array{
     *     type: string,
     *     title: string,
     *     company_id: int,
     *     classifier_category_id: int,
     *     responsible_user_id?: int,
     *     trade_direction?: string|null,
     *     description?: string|null,
     *     visibility?: string,
     *     customer_contact_name?: string|null,
     *     customer_contact_email?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     storage_years?: int,
     *     source_procedure_id?: int|null
     * } $data Валидированные поля
     * @param User $author Создатель (created_by)
     * @return Procedure Черновик с связями
     */
    public function execute(array $data, User $author): Procedure
    {
        return DB::transaction(function () use ($data, $author): Procedure {
            $type = ProcedureType::from($data['type']);

            $procedure = Procedure::query()->create([
                'number' => $this->procedures->generateNumber(),
                'type' => $type,
                'trade_direction' => $data['trade_direction'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => ProcedureStatus::Draft,
                'visibility' => $data['visibility'] ?? ProcedureVisibility::Open->value,
                'company_id' => $data['company_id'],
                'classifier_category_id' => $data['classifier_category_id'],
                'responsible_user_id' => $data['responsible_user_id'] ?? $author->id,
                'created_by' => $author->id,
                'customer_contact_name' => $data['customer_contact_name'] ?? null,
                'customer_contact_email' => $data['customer_contact_email'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'storage_years' => $data['storage_years'] ?? 3,
                'source_procedure_id' => $data['source_procedure_id'] ?? null,
            ]);

            if ($type === ProcedureType::Auction) {
                $this->createDefaultAuctionSettings($procedure);
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

    /**
     * Создаёт настройки аукциона со значениями по умолчанию из миграции.
     *
     * @param Procedure $procedure Процедура типа auction
     * @return void
     */
    private function createDefaultAuctionSettings(Procedure $procedure): void
    {
        AuctionSetting::query()->create([
            'procedure_id' => $procedure->id,
            'bid_mode' => BidMode::Standard,
            'auction_mode' => AuctionMode::Decrease,
            'extension_minutes' => 5,
            'extension_trigger_minutes' => null,
            'idle_timeout_minutes' => 30,
            'forbid_equal_bids' => true,
            'winner_mode' => WinnerMode::PerLot,
            'only_admitted_from_rfp' => false,
        ]);
    }
}
