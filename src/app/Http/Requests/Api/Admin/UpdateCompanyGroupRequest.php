<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос обновления группы компаний холдинга (только super_admin).
 */
class UpdateCompanyGroupRequest extends FormRequest
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
     * Правила частичного обновления группы компаний.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
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
            'name.required' => 'Название группы компаний обязательно для заполнения.',
            'name.string' => 'Название группы компаний должно быть строкой.',
            'name.max' => 'Название группы компаний не должно превышать :max символов.',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
            'is_active.boolean' => 'Поле активности должно быть логическим значением.',
        ];
    }
}
