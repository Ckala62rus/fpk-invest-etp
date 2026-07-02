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

    /**
     * Пользователь, подавший сообщение о коррупции (если был авторизован).
     *
     * Нужен для связи обращения с учётной записью и обработки сообщений администратором.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
