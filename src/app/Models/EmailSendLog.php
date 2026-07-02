<?php

namespace App\Models;

use App\Enums\EmailSendStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запись истории отправки email.
 *
 * @property int $id Идентификатор записи отправки
 * @property int|null $template_id Шаблон письма
 * @property string $recipient_email Email получателя
 * @property int|null $user_id Пользователь-получатель
 * @property string $subject Тема отправленного письма
 * @property EmailSendStatus $status Статус отправки письма
 * @property string|null $error Текст ошибки при неудачной отправке
 * @property array|null $payload Данные для шаблона
 * @property \Illuminate\Support\Carbon|null $sent_at Фактическое время отправки
 * @property \Illuminate\Support\Carbon|null $created_at Время постановки в очередь
 */
class EmailSendLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'template_id',
        'recipient_email',
        'user_id',
        'subject',
        'status',
        'error',
        'payload',
        'sent_at',
    ];

    /**
     * Преобразование атрибутов записи отправки email в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmailSendStatus::class,
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Шаблон письма, по которому была выполнена отправка.
     *
     * Нужен для анализа истории рассылок и отладки проблем с конкретным шаблоном уведомлений.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Пользователь-получатель письма (если известен).
     *
     * Используется для отображения истории уведомлений в карточке пользователя.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
