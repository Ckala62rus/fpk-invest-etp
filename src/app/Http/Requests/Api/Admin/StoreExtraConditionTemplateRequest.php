<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\CustomFieldType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос создания шаблона дополнительного условия аукциона (super_admin).
 */
class StoreExtraConditionTemplateRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware `role:super_admin`.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила создания шаблона условия.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string', Rule::in(array_column(CustomFieldType::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
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
            'name.required' => 'Название условия обязательно для заполнения.',
            'name.string' => 'Название условия должно быть строкой.',
            'name.max' => 'Название условия не должно превышать :max символов.',
            'field_type.required' => 'Тип значения условия обязателен для заполнения.',
            'field_type.in' => 'Тип значения условия указан неверно.',
            'is_active.boolean' => 'Поле активности должно быть логическим значением.',
        ];
    }
}
