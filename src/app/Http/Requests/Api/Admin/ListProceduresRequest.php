<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос списка ТЗП в админке (super_admin | trade_admin | auditor).
 */
class ListProceduresRequest extends FormRequest
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
     * Нормализует пустые query-параметры в null.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nullable = [
            'search',
            'type',
            'status',
            'visibility',
            'company_id',
            'classifier_category_id',
            'responsible_user_id',
            'trashed',
            'per_page',
        ];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Правила фильтров админского списка ТЗП.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(array_column(ProcedureType::cases(), 'value'))],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(array_column(ProcedureStatus::cases(), 'value'))],
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(array_column(ProcedureVisibility::cases(), 'value'))],
            'company_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'classifier_category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'responsible_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
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
            'type.in' => 'Тип процедуры указан неверно.',
            'status.in' => 'Статус процедуры указан неверно.',
            'visibility.in' => 'Открытость процедуры указана неверно.',
            'company_id.integer' => 'Идентификатор компании должен быть целым числом.',
            'classifier_category_id.integer' => 'Идентификатор категории должен быть целым числом.',
            'responsible_user_id.integer' => 'Идентификатор ответственного должен быть целым числом.',
            'trashed.in' => 'Параметр trashed должен быть одним из: without, with, only.',
            'per_page.integer' => 'Количество записей на странице должно быть целым числом.',
            'per_page.min' => 'Количество записей на странице должно быть не меньше :min.',
            'per_page.max' => 'Количество записей на странице не должно превышать :max.',
        ];
    }
}
