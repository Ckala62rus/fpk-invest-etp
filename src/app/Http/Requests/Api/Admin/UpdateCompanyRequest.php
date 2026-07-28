<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос обновления предприятия-заказчика (только super_admin).
 */
class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_group_id' => ['sometimes', 'required', 'integer', Rule::exists('company_groups', 'id')->whereNull('deleted_at')],
            'name' => ['sometimes', 'required', 'string', 'max:500'],
            'inn' => ['sometimes', 'nullable', 'string', 'max:12'],
            'is_external' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_group_id.required' => 'Укажите группу компаний.',
            'company_group_id.integer' => 'Идентификатор группы компаний должен быть целым числом.',
            'company_group_id.exists' => 'Указанная группа компаний не найдена.',
            'name.required' => 'Наименование предприятия обязательно для заполнения.',
            'name.string' => 'Наименование предприятия должно быть строкой.',
            'name.max' => 'Наименование предприятия не должно превышать :max символов.',
            'inn.string' => 'ИНН должен быть строкой.',
            'inn.max' => 'ИНН не должен превышать :max символов.',
            'is_external.boolean' => 'Поле внешнего заказчика должно быть логическим значением.',
            'is_active.boolean' => 'Поле активности должно быть логическим значением.',
        ];
    }
}
