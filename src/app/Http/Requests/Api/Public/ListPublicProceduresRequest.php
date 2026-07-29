<?php

namespace App\Http\Requests\Api\Public;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос публичного списка ТЗП (торгово-закупочных процедур) для гостя.
 */
class ListPublicProceduresRequest extends FormRequest
{
    /**
     * Публичный endpoint — доступен без авторизации.
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
            'company_id',
            'classifier_category_id',
            'per_page',
        ];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Правила фильтров публичного списка ТЗП.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(array_column(ProcedureType::cases(), 'value'))],
            'status' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    ProcedureStatus::Published->value,
                    ProcedureStatus::Accepting->value,
                    ProcedureStatus::Review->value,
                    ProcedureStatus::AuctionPending->value,
                    ProcedureStatus::InProgress->value,
                    ProcedureStatus::Completed->value,
                ]),
            ],
            'company_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'classifier_category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
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
            'type.string' => 'Тип процедуры должен быть строкой.',
            'type.in' => 'Тип процедуры указан неверно.',
            'status.string' => 'Статус процедуры должен быть строкой.',
            'status.in' => 'Статус процедуры указан неверно или недоступен для публичного списка.',
            'company_id.integer' => 'Идентификатор компании должен быть целым числом.',
            'company_id.min' => 'Идентификатор компании должен быть положительным.',
            'classifier_category_id.integer' => 'Идентификатор категории должен быть целым числом.',
            'classifier_category_id.min' => 'Идентификатор категории должен быть положительным.',
            'per_page.integer' => 'Количество записей на странице должно быть целым числом.',
            'per_page.min' => 'Количество записей на странице должно быть не меньше :min.',
            'per_page.max' => 'Количество записей на странице не должно превышать :max.',
        ];
    }
}
