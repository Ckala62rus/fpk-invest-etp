<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос подачи коммерческого предложения (КП) участником.
 */
class SubmitProposalRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей и Action.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила подачи КП.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contract_form_agreed' => ['required', 'accepted'],
            'field_values' => ['sometimes', 'array'],
            'field_values.*.procedure_custom_field_id' => ['required', 'integer', 'min:1'],
            'field_values.*.value' => ['sometimes', 'nullable', 'string', 'max:5000'],
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
            'contract_form_agreed.required' => 'Необходимо согласие с формой договора.',
            'contract_form_agreed.accepted' => 'Необходимо согласие с формой договора.',
            'field_values.array' => 'Значения полей должны быть массивом.',
            'field_values.*.procedure_custom_field_id.required' => 'Укажите идентификатор поля процедуры.',
            'field_values.*.procedure_custom_field_id.integer' => 'Идентификатор поля должен быть целым числом.',
            'field_values.*.procedure_custom_field_id.min' => 'Идентификатор поля указан неверно.',
            'field_values.*.value.string' => 'Значение поля должно быть строкой.',
            'field_values.*.value.max' => 'Значение поля не должно превышать :max символов.',
        ];
    }
}
