<?php

namespace App\Models;

use App\Enums\PasswordResetAdminStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запрос восстановления доступа через главного администратора.
 *
 * @property int $id Идентификатор
 * @property int|null $user_id Пользователь (если найден по ИНН)
 * @property string|null $inn ИНН из обращения
 * @property string|null $message Текст обращения
 * @property PasswordResetAdminStatus $status Статус обработки запроса
 * @property int|null $resolved_by Администратор, обработавший запрос
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PasswordResetAdminRequest extends Model
{
    protected $fillable = [
        'user_id',
        'inn',
        'message',
        'status',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PasswordResetAdminStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
