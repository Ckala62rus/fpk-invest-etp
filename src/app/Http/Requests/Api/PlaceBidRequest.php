<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос подачи ставки на лот аукциона (фаза 8.3).
 */
class PlaceBidRequest extends FormRequest
{
    /**
     * @return bool
     */
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
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма ставки обязательна для заполнения.',
            'amount.numeric' => 'Сумма ставки должна быть числом.',
            'amount.gt' => 'Сумма ставки должна быть больше нуля.',
        ];
    }
}
