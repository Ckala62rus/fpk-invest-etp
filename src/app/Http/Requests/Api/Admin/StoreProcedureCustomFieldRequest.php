<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Запрос создания настраиваемого поля ТЗП.
 */
class StoreProcedureCustomFieldRequest extends FormRequest
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
     * Правила создания настраиваемого поля.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(array_column(CustomFieldScope::cases(), 'value'))],
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string', Rule::in(array_column(CustomFieldType::cases(), 'value'))],
            'options' => ['sometimes', 'nullable', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Для select варианты options обязательны.
     *
     * @param Validator $validator Валидатор Laravel
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('field_type') === CustomFieldType::Select->value) {
                $options = $this->input('options');
                if (! is_array($options) || $options === []) {
                    $validator->errors()->add(
                        'options',
                        'Для типа select необходимо указать хотя бы один вариант в options.',
                    );
                }
            }
        });
    }

    /**
     * Русские сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.required' => 'Область применения поля обязательна для заполнения.',
            'scope.in' => 'Область применения поля указана неверно.',
            'label.required' => 'Подпись поля обязательна для заполнения.',
            'label.string' => 'Подпись поля должна быть строкой.',
            'label.max' => 'Подпись поля не должна превышать :max символов.',
            'field_type.required' => 'Тип поля обязателен для заполнения.',
            'field_type.in' => 'Тип поля указан неверно.',
            'options.array' => 'Варианты выбора должны быть массивом.',
            'options.min' => 'Необходимо указать хотя бы один вариант выбора.',
            'options.*.required' => 'Вариант выбора не может быть пустым.',
            'options.*.string' => 'Вариант выбора должен быть строкой.',
            'options.*.max' => 'Вариант выбора не должен превышать :max символов.',
            'is_required.boolean' => 'Признак обязательности должен быть логическим значением.',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
        ];
    }
}
