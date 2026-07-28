<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос списка категорий классификатора (только super_admin).
 */
class ListClassifierCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['company_group_id', 'search', 'is_active', 'trashed', 'per_page'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_group_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'trashed' => ['sometimes', 'nullable', 'string', Rule::in(['without', 'with', 'only'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_group_id.integer' => 'Идентификатор группы компаний должен быть целым числом.',
            'company_group_id.min' => 'Идентификатор группы компаний должен быть не меньше :min.',
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
