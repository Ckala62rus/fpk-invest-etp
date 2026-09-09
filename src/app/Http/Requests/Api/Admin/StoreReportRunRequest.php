<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запуск формирования отчёта (фаза 10.6).
 */
class StoreReportRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(array_column(ReportFormat::cases(), 'value'))],
            'filters' => ['nullable', 'array'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date'],
            'filters.status' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'format.required' => 'Укажите формат файла.',
            'format.in' => 'Формат: pdf, xlsx или doc.',
            'filters.array' => 'Фильтры должны быть объектом.',
            'filters.date_from.date' => 'Дата начала указана неверно.',
            'filters.date_to.date' => 'Дата окончания указана неверно.',
            'filters.status.string' => 'Статус должен быть строкой.',
            'filters.status.max' => 'Статус не должен превышать :max символов.',
        ];
    }
}
