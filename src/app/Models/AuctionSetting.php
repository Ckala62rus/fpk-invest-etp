<?php

namespace App\Models;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\WinnerMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Настройки электронного аукциона.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура-аукцион
 * @property BidMode $bid_mode Режим ставки
 * @property AuctionMode $auction_mode Направление аукциона
 * @property int $extension_minutes Продление времени при ставке (минуты)
 * @property int|null $extension_trigger_minutes За сколько минут до конца включается продление
 * @property int $idle_timeout_minutes Автозавершение при отсутствии ставок (минуты)
 * @property bool $forbid_equal_bids Запрет одинаковых ставок
 * @property WinnerMode $winner_mode Способ определения победителя
 * @property bool $only_admitted_from_rfp Только участники, допущенные на 1-м этапе
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AuctionSetting extends Model
{
    protected $fillable = [
        'procedure_id',
        'bid_mode',
        'auction_mode',
        'extension_minutes',
        'extension_trigger_minutes',
        'idle_timeout_minutes',
        'forbid_equal_bids',
        'winner_mode',
        'only_admitted_from_rfp',
    ];

    /**
     * Преобразование атрибутов настроек аукциона в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bid_mode' => BidMode::class,
            'auction_mode' => AuctionMode::class,
            'winner_mode' => WinnerMode::class,
            'extension_minutes' => 'integer',
            'extension_trigger_minutes' => 'integer',
            'idle_timeout_minutes' => 'integer',
            'forbid_equal_bids' => 'boolean',
            'only_admitted_from_rfp' => 'boolean',
        ];
    }

    /**
     * Процедура-аукцион, к которой относятся эти настройки.
     *
     * Нужен для применения правил торгов (продление, режим ставок, определение победителя) при проведении аукциона.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
