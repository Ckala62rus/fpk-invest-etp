<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сессия посещения страницы аукциона.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура-аукцион
 * @property int|null $user_id Участник (null для гостя)
 * @property \Illuminate\Support\Carbon $first_seen_at Первый визит
 * @property \Illuminate\Support\Carbon $last_seen_at Последняя активность
 * @property bool $is_online Сейчас на странице аукциона
 */
class AuctionSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_id',
        'user_id',
        'first_seen_at',
        'last_seen_at',
        'is_online',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
