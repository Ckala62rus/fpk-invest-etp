<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Запись аудита действий пользователей на площадке.
 *
 * @property int $id Идентификатор записи аудита
 * @property string|null $log_name Канал лога
 * @property string $description Описание действия
 * @property string|null $subject_type Тип объекта действия
 * @property int|null $subject_id Идентификатор объекта действия
 * @property string|null $causer_type Тип инициатора действия
 * @property int|null $causer_id Идентификатор инициатора действия
 * @property array|null $properties Дополнительные данные действия
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время действия
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
    ];

    /**
     * Преобразование атрибутов записи аудита в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * Объект, над которым было выполнено действие (процедура, заявка, пользователь и т.д.).
     *
     * Нужен для навигации из журнала аудита к сущности и отображения контекста действия.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Пользователь или системный актор, инициировавший действие.
     *
     * Используется для отображения автора действия в журнале аудита и фильтрации по инициатору.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
