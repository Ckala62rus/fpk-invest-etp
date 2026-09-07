<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос отмены ставки аукциона администратором (фаза 8.6).
 */
class CancelBidRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Причина отмены ставки обязательна для заполнения.',
            'reason.string' => 'Причина отмены должна быть строкой.',
            'reason.min' => 'Причина отмены должна содержать не менее :min символов.',
            'reason.max' => 'Причина отмены не должна превышать :max символов.',
        ];
    }
}
