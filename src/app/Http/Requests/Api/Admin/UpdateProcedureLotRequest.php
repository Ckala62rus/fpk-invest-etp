<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос обновления лота ТЗП.
 */
class UpdateProcedureLotRequest extends FormRequest
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
     * Правила частичного обновления лота.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:500'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_price' => ['sometimes', 'numeric', 'min:0'],
            'bid_step' => ['sometimes', 'numeric', 'min:0.01'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
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
            'name.string' => 'Наименование лота должно быть строкой.',
            'name.max' => 'Наименование лота не должно превышать :max символов.',
            'unit.string' => 'Единица измерения должна быть строкой.',
            'unit.max' => 'Единица измерения не должна превышать :max символов.',
            'quantity.numeric' => 'Количество должно быть числом.',
            'quantity.min' => 'Количество не может быть отрицательным.',
            'start_price.numeric' => 'Начальная цена должна быть числом.',
            'start_price.min' => 'Начальная цена не может быть отрицательной.',
            'bid_step.numeric' => 'Шаг ставки должен быть числом.',
            'bid_step.min' => 'Шаг ставки должен быть не меньше :min.',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
        ];
    }
}
