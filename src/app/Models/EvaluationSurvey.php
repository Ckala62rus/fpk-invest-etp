<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Опрос заказчика после завершения процедуры.
 *
 * @property int $id Идентификатор опроса
 * @property int $procedure_id Завершённая процедура
 * @property string $token Токен ссылки на опрос
 * @property \Illuminate\Support\Carbon|null $sent_at Дата отправки опроса
 * @property \Illuminate\Support\Carbon|null $completed_at Дата заполнения опроса
 * @property int $reminder_stage Этап напоминаний (1 нед, 3 дня, …)
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class EvaluationSurvey extends Model
{
    protected $fillable = [
        'procedure_id',
        'token',
        'sent_at',
        'completed_at',
        'reminder_stage',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_stage' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class, 'survey_id');
    }
}
