<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос списка групп компаний холдинга (только super_admin).
 */
class ListCompanyGroupsRequest extends FormRequest
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
     * Нормализует пустые query-параметры в null.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nullable = ['search', 'is_active', 'trashed', 'per_page'];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Правила фильтров и пагинации.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'trashed' => ['sometimes', 'nullable', 'string', Rule::in(['without', 'with', 'only'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
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
            'search.string' => 'Строка поиска должна быть текстом.',
            'search.max' => 'Строка поиска не должна превышать :max символов.',
            'is_active.boolean' => 'Поле активности должно быть логическим значением.',
            'trashed.string' => 'Параметр trashed должен быть строкой.',
            'trashed.in' => 'Параметр trashed должен быть одним из: without, with, only.',
            'per_page.integer' => 'Количество записей на странице должно быть целым числом.',
            'per_page.min' => 'Количество записей на странице должно быть не меньше :min.',
            'per_page.max' => 'Количество записей на странице не должно превышать :max.',
        ];
    }
}
