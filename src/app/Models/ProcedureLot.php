<?php

namespace App\Models;

use Database\Factories\ProcedureLotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Лот аукциона.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property int $sort_order Порядок сортировки
 * @property string $name Наименование лота
 * @property string|null $unit Единица измерения
 * @property string|null $quantity Потребность
 * @property string $start_price Начальная цена
 * @property string $bid_step Минимальный шаг ставки
 * @property string|null $current_price Текущая лучшая цена
 * @property int|null $winner_user_id Победитель по лоту
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureLot extends Model
{
    /** @use HasFactory<ProcedureLotFactory> */
    use HasFactory;

    protected $fillable = [
        'procedure_id',
        'sort_order',
        'name',
        'unit',
        'quantity',
        'start_price',
        'bid_step',
        'current_price',
        'winner_user_id',
    ];

    /**
     * Преобразование атрибутов лота в типы PHP (цены, количество).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'quantity' => 'decimal:3',
            'start_price' => 'decimal:2',
            'bid_step' => 'decimal:2',
            'current_price' => 'decimal:2',
        ];
    }

    /**
     * Торгово-закупочная процедура-аукцион, к которой относится этот лот.
     *
     * Нужен для навигации от ставки к карточке аукциона и проверки статуса торгов.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Участник-победитель по данному лоту после завершения аукциона.
     *
     * Заполняется при определении итогов; отображается в протоколе и профиле победителя.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    /**
     * Все ставки участников по этому лоту (включая отменённые администратором).
     *
     * Используется для расчёта текущей цены, валидации шага ставки и формирования протокола.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class, 'lot_id');
    }
}
