<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос списка записей аудита ЭТП (электронной торговой площадки).
 *
 * Query: log_name, event, causer_id, subject_type, subject_id, date_from, date_to, per_page.
 * Доступ: middleware `role:super_admin|trade_admin|auditor` (право activity_log.view).
 */
class ListActivityLogsRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware `role`.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализует пустые query-параметры в null до валидации.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nullable = [
            'log_name',
            'event',
            'causer_id',
            'subject_type',
            'subject_id',
            'date_from',
            'date_to',
            'per_page',
        ];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Правила фильтров и пагинации журнала аудита.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'log_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'event' => ['sometimes', 'nullable', 'string', 'max:100'],
            'causer_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'subject_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
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
            'log_name.string' => 'Канал лога должен быть строкой.',
            'log_name.max' => 'Канал лога не должен превышать :max символов.',
            'event.string' => 'Тип события должен быть строкой.',
            'event.max' => 'Тип события не должен превышать :max символов.',
            'causer_id.integer' => 'Идентификатор инициатора должен быть целым числом.',
            'causer_id.min' => 'Идентификатор инициатора должен быть не меньше :min.',
            'subject_type.string' => 'Тип субъекта должен быть строкой.',
            'subject_type.max' => 'Тип субъекта не должен превышать :max символов.',
            'subject_id.integer' => 'Идентификатор субъекта должен быть целым числом.',
            'subject_id.min' => 'Идентификатор субъекта должен быть не меньше :min.',
            'date_from.date' => 'Дата начала периода должна быть корректной датой.',
            'date_to.date' => 'Дата конца периода должна быть корректной датой.',
            'date_to.after_or_equal' => 'Дата конца периода не может быть раньше даты начала.',
            'per_page.integer' => 'Количество записей на странице должно быть целым числом.',
            'per_page.min' => 'Количество записей на странице должно быть не меньше :min.',
            'per_page.max' => 'Количество записей на странице не должно превышать :max.',
        ];
    }
}
