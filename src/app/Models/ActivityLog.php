<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Запись аудита действий пользователей на площадке.
 *
 * Расширяет Spatie Activity Log; таблица activity_log создана кастомной миграцией ЭТП.
 *
 * @property int $id Идентификатор записи аудита
 * @property string|null $log_name Канал лога
 * @property string $description Описание действия
 * @property string|null $event Тип события (created, updated, deleted)
 * @property string|null $subject_type Тип объекта действия
 * @property int|null $subject_id Идентификатор объекта действия
 * @property string|null $causer_type Тип инициатора действия
 * @property int|null $causer_id Идентификатор инициатора действия
 * @property array|null $properties Дополнительные данные действия
 * @property string|null $batch_uuid UUID пакета связанных записей
 * @property \Illuminate\Support\Carbon|null $created_at Дата и время действия
 */
class ActivityLog extends SpatieActivity
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

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
        return parent::subject();
    }

    /**
     * Пользователь или системный актор, инициировавший действие.
     *
     * Используется для отображения автора действия в журнале аудита и фильтрации по инициатору.
     */
    public function causer(): MorphTo
    {
        return parent::causer();
    }
}
