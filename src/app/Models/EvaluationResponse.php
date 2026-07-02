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

    protected function casts(): array
    {
        return [
            'answer' => 'array',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(EvaluationSurvey::class, 'survey_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationSurveyTemplate::class, 'question_id');
    }
}
