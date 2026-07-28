<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос синхронизации подписок участника на категории и группы компаний.
 */
class SyncSubscriptionsRequest extends FormRequest
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
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', Rule::exists('classifier_categories', 'id')->whereNull('deleted_at')],
            'company_group_ids' => ['sometimes', 'array'],
            'company_group_ids.*' => ['integer', Rule::exists('company_groups', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_ids.array' => 'Список категорий должен быть массивом.',
            'category_ids.*.integer' => 'Идентификатор категории должен быть целым числом.',
            'category_ids.*.exists' => 'Указанная категория классификатора не найдена.',
            'company_group_ids.array' => 'Список групп компаний должен быть массивом.',
            'company_group_ids.*.integer' => 'Идентификатор группы компаний должен быть целым числом.',
            'company_group_ids.*.exists' => 'Указанная группа компаний не найдена.',
        ];
    }
}
