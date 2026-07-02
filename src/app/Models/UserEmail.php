<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Дополнительный email пользователя.
 *
 * @property int $id Идентификатор
 * @property int $user_id Пользователь
 * @property string $email Дополнительный email
 * @property \Illuminate\Support\Carbon|null $created_at Дата добавления
 */
class UserEmail extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
