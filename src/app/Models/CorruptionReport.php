<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сообщение о коррупционной составляющей.
 *
 * @property int $id Идентификатор сообщения
 * @property int|null $user_id Пользователь (если авторизован)
 * @property string|null $name Имя заявителя
 * @property string|null $email Email заявителя
 * @property string $message Текст сообщения о коррупции
 * @property \Illuminate\Support\Carbon|null $created_at Дата подачи
 */
class CorruptionReport extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
