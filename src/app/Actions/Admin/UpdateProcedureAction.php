<?php

namespace App\Actions\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\WinnerMode;
use App\Exceptions\DomainException;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Обновляет черновик ТЗП и пишет запись в procedure_change_logs.
 *
 * Фаза 5.1 + 5.9: редактирование только draft; каждое изменение логируется.
 */
class UpdateProcedureAction
{
    /**
     * Обновляет поля черновика процедуры.
     *
     * @param Procedure $procedure Целевая процедура
     * @param array<string, mixed> $data Валидированные поля (частичное обновление)
     * @param User|null $editor Автор изменения (для change log)
     * @return Procedure Обновлённая процедура со связями
     *
     * @throws DomainException Если процедура не в статусе draft
     */
    public function execute(Procedure $procedure, array $data, ?User $editor = null): Procedure
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Редактировать можно только черновик процедуры.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($procedure, $data, $editor): Procedure {
            $before = $procedure->only(array_keys($data));

            $procedure->update($data);
            $procedure->refresh();

            $after = $procedure->only(array_keys($data));
            $diff = $this->buildDiff($before, $after);

            if ($diff !== [] && $editor !== null) {
                ProcedureChangeLog::query()->create([
                    'procedure_id' => $procedure->id,
                    'changed_by' => $editor->id,
                    'change_summary' => 'Обновление черновика процедуры',
                    'diff' => $diff,
                    'approval_status' => ApprovalStatus::Approved,
                    'approved_by' => $editor->id,
                    'approved_at' => now(),
                ]);
            }

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

    /**
     * Строит diff только по реально изменившимся полям.
     *
     * @param array<string, mixed> $before Значения до update
     * @param array<string, mixed> $after Значения после update
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function buildDiff(array $before, array $after): array
    {
        $diff = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            $oldComparable = $oldValue instanceof \BackedEnum ? $oldValue->value : $oldValue;
            $newComparable = $newValue instanceof \BackedEnum ? $newValue->value : $newValue;

            if ($oldComparable instanceof \DateTimeInterface) {
                $oldComparable = $oldComparable->format('c');
            }
            if ($newComparable instanceof \DateTimeInterface) {
                $newComparable = $newComparable->format('c');
            }

            if ($oldComparable != $newComparable) {
                $diff[$key] = [
                    'old' => $oldComparable,
                    'new' => $newComparable,
                ];
            }
        }

        return $diff;
    }
}
