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

    /**
     * Преобразование атрибутов запроса восстановления в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PasswordResetAdminStatus::class,
        ];
    }

    /**
     * Пользователь, для которого создан запрос восстановления доступа.
     *
     * Нужен для связи обращения с учётной записью при обработке запроса главным администратором.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Администратор, обработавший запрос восстановления доступа.
     *
     * Используется для аудита обработки обращений и отображения ответственного лица.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
