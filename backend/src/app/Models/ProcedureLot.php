<?php

namespace App\Models;

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

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class, 'lot_id');
    }
}
