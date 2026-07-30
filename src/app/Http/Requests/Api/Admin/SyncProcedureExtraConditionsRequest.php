<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос синхронизации значений доп. условий для черновика ТЗП.
 *
 * Полная замена набора: передаётся массив conditions[{template_id, value}].
 */
class SyncProcedureExtraConditionsRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила синхронизации значений условий.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conditions' => ['required', 'array'],
            'conditions.*.template_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('procedure_extra_condition_templates', 'id')->where('is_active', true),
            ],
            'conditions.*.value' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Русские сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'conditions.required' => 'Список условий обязателен для заполнения.',
            'conditions.array' => 'Список условий должен быть массивом.',
            'conditions.*.template_id.required' => 'Идентификатор шаблона условия обязателен.',
            'conditions.*.template_id.integer' => 'Идентификатор шаблона условия должен быть целым числом.',
            'conditions.*.template_id.distinct' => 'Шаблон условия не должен повторяться в списке.',
            'conditions.*.template_id.exists' => 'Указанный шаблон условия не найден или неактивен.',
            'conditions.*.value.string' => 'Значение условия должно быть строкой.',
            'conditions.*.value.max' => 'Значение условия не должно превышать :max символов.',
        ];
    }
}
