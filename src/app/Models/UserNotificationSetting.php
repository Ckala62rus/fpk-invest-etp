<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Настройки email-оповещений пользователя.
 *
 * @property int $id Идентификатор
 * @property int $user_id Пользователь
 * @property bool $all_disabled Отписаться от всех рассылок ЭТП
 * @property bool $notify_new_auctions Оповещать о новых аукционах
 * @property bool $notify_new_procedures Оповещать о новых ТЗП по выбранным категориям
 * @property bool $notify_day_before Напоминание за день до начала
 * @property bool $notify_hour_before Напоминание за час до начала
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'all_disabled',
        'notify_new_auctions',
        'notify_new_procedures',
        'notify_day_before',
        'notify_hour_before',
    ];

    /**
     * Преобразование флагов настроек оповещений в тип boolean.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'all_disabled' => 'boolean',
            'notify_new_auctions' => 'boolean',
            'notify_new_procedures' => 'boolean',
            'notify_day_before' => 'boolean',
            'notify_hour_before' => 'boolean',
        ];
    }

    /**
     * Пользователь, для которого заданы настройки email-оповещений.
     *
     * Нужен для проверки подписок при рассылке уведомлений о новых процедурах и напоминаниях.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
