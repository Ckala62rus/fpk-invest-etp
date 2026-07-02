<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Токен восстановления пароля.
 *
 * @property int $id Идентификатор
 * @property int $user_id Пользователь
 * @property string $token Токен восстановления пароля
 * @property \Illuminate\Support\Carbon $expires_at Срок действия ссылки
 * @property \Illuminate\Support\Carbon|null $used_at Дата использования токена
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 */
class PasswordResetToken extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
    ];

    /**
     * Преобразование атрибутов токена восстановления пароля в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Пользователь, для которого выпущен токен восстановления пароля.
     *
     * Нужен для привязки ссылки восстановления к учётной записи и инвалидации токена после смены пароля.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
