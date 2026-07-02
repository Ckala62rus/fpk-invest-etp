<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон email-уведомления.
 *
 * @property int $id Идентификатор шаблона
 * @property string $code Код шаблона: registration_confirm, auction_invite и т.д.
 * @property string $name Название шаблона
 * @property string $subject Тема письма
 * @property string $body_html HTML-тело письма с плейсхолдерами
 * @property NotificationEventType|null $event_type Тип: по событию или по расписанию
 * @property bool $is_active Активен ли шаблон
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'subject',
        'body_html',
        'event_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'is_active' => 'boolean',
        ];
    }

    public function emailSendLogs(): HasMany
    {
        return $this->hasMany(EmailSendLog::class, 'template_id');
    }
}
