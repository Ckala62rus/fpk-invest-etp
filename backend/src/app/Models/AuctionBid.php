<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ставка участника на аукционе.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура-аукцион
 * @property int $lot_id Лот
 * @property int $user_id Участник
 * @property string $amount Сумма ставки
 * @property bool $is_cancelled Ставка отменена администратором
 * @property int|null $cancelled_by Кто отменил ставку
 * @property \Illuminate\Support\Carbon|null $cancelled_at Дата отмены ставки
 * @property string|null $cancel_reason Причина отмены
 * @property string|null $ip_address IP-адрес при подаче ставки
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время ставки
 */
class AuctionBid extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'procedure_id',
        'lot_id',
        'user_id',
        'amount',
        'is_cancelled',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProcedureLot::class, 'lot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
