<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ответ заказчика на вопрос опроса качества.
 *
 * @property int $id Идентификатор ответа
 * @property int $survey_id Опрос
 * @property int $question_id Вопрос
 * @property array $answer Ответ заказчика
 * @property \Illuminate\Support\Carbon|null $created_at Дата ответа
 */
class EvaluationResponse extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'survey_id',
        'question_id',
        'answer',
    ];

    /**
     * Преобразование атрибута ответа опроса в тип PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer' => 'array',
        ];
    }

    /**
     * Опрос качества закупки, к которому относится этот ответ.
     *
     * Нужен для агрегации результатов опроса и формирования отчёта по оценке процедуры.
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(EvaluationSurvey::class, 'survey_id');
    }

    /**
     * Шаблон вопроса, на который дан ответ.
     *
     * Используется для сопоставления ответа с текстом вопроса и типом поля (рейтинг, да/нет, текст).
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationSurveyTemplate::class, 'question_id');
    }
}
