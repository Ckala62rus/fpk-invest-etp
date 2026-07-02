<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Жалоба пользователя (кнопка «Подать жалобу»).
 *
 * @property int $id Идентификатор жалобы
 * @property int|null $user_id Пользователь (если авторизован)
 * @property string|null $name Имя заявителя
 * @property string|null $email Email заявителя
 * @property string $subject Тема жалобы
 * @property string $message Текст жалобы
 * @property ComplaintStatus $status Статус обработки жалобы
 * @property \Illuminate\Support\Carbon|null $created_at Дата подачи
 */
class Complaint extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
