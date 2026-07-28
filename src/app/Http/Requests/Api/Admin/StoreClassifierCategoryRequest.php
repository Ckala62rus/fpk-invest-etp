<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос создания категории классификатора (только super_admin).
 */
class StoreClassifierCategoryRequest extends FormRequest
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
            'company_group_id' => ['required', 'integer', Rule::exists('company_groups', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
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
            'name.required' => 'Название категории обязательно для заполнения.',
            'name.string' => 'Название категории должно быть строкой.',
            'name.max' => 'Название категории не должно превышать :max символов.',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
            'is_active.boolean' => 'Поле активности должно быть логическим значением.',
        ];
    }
}
