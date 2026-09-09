<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Создание шаблона отчёта (фаза 10.1).
 */
class StoreReportTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'query_config' => ['required', 'array'],
            'query_config.source' => ['required', 'string', 'in:procedures,auction_bids'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название шаблона обязательно.',
            'name.string' => 'Название должно быть строкой.',
            'name.max' => 'Название не должно превышать :max символов.',
            'query_config.required' => 'Конфигурация выборки обязательна.',
            'query_config.array' => 'Конфигурация выборки должна быть объектом.',
            'query_config.source.required' => 'Укажите источник данных.',
            'query_config.source.in' => 'Источник может быть procedures или auction_bids.',
            'columns.required' => 'Укажите колонки отчёта.',
            'columns.array' => 'Колонки должны быть массивом.',
            'columns.min' => 'Нужна хотя бы одна колонка.',
            'columns.*.required' => 'Имя колонки обязательно.',
            'columns.*.string' => 'Имя колонки должно быть строкой.',
        ];
    }
}
