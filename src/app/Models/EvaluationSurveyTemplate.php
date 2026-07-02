<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон вопроса опроса качества закупки.
 *
 * @property int $id Идентификатор вопроса
 * @property string $question Текст вопроса
 * @property string $field_type Тип ответа: boolean, rating, text и т.д.
 * @property array|null $options Варианты ответа
 * @property bool $is_required Обязательный вопрос
 * @property int $sort_order Порядок вопроса
 * @property array|null $conditional_logic Условная логика показа вопроса
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class EvaluationSurveyTemplate extends Model
{
    protected $fillable = [
        'question',
        'field_type',
        'options',
        'is_required',
        'sort_order',
        'conditional_logic',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'conditional_logic' => 'array',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EvaluationResponse::class, 'question_id');
    }
}
