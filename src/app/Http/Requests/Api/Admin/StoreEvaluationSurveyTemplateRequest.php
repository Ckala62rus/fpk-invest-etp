<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Создание вопроса опроса качества (фаза 9.1).
 */
class StoreEvaluationSurveyTemplateRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:500'],
            'field_type' => ['required', 'string', Rule::in(['rating', 'text', 'boolean'])],
            'options' => ['nullable', 'array'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'conditional_logic' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Текст вопроса обязателен.',
            'question.string' => 'Вопрос должен быть строкой.',
            'question.max' => 'Вопрос не должен превышать :max символов.',
            'field_type.required' => 'Тип поля обязателен.',
            'field_type.string' => 'Тип поля должен быть строкой.',
            'field_type.in' => 'Тип поля указан неверно.',
            'options.array' => 'Варианты ответа должны быть массивом.',
            'is_required.boolean' => 'Признак обязательности должен быть логическим.',
            'sort_order.integer' => 'Порядок должен быть числом.',
            'sort_order.min' => 'Порядок не может быть отрицательным.',
            'sort_order.max' => 'Порядок не должен превышать :max.',
            'conditional_logic.array' => 'Условная логика должна быть массивом.',
        ];
    }
}
