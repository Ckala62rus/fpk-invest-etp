<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Отправка ответов на опрос качества по токену (фаза 9.4).
 */
class StoreEvaluationResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:evaluation_survey_templates,id'],
            'answers.*.value' => ['required'],
            'contractor_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'product_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Передайте ответы на вопросы.',
            'answers.array' => 'Ответы должны быть массивом.',
            'answers.min' => 'Нужен хотя бы один ответ.',
            'answers.*.question_id.required' => 'Укажите вопрос.',
            'answers.*.question_id.integer' => 'Идентификатор вопроса должен быть числом.',
            'answers.*.question_id.exists' => 'Вопрос не найден.',
            'answers.*.value.required' => 'Укажите значение ответа.',
            'contractor_score.integer' => 'Оценка подрядчика должна быть числом.',
            'contractor_score.min' => 'Оценка подрядчика не менее :min.',
            'contractor_score.max' => 'Оценка подрядчика не более :max.',
            'product_score.integer' => 'Оценка продукции должна быть числом.',
            'product_score.min' => 'Оценка продукции не менее :min.',
            'product_score.max' => 'Оценка продукции не более :max.',
            'comment.max' => 'Комментарий не должен превышать :max символов.',
        ];
    }
}
