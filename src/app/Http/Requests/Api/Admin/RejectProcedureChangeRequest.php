<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос отклонения изменения документации ТЗП.
 */
class RejectProcedureChangeRequest extends FormRequest
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
            'reason.required' => 'Укажите причину отклонения изменения документации.',
            'reason.string' => 'Причина должна быть строкой.',
            'reason.min' => 'Причина должна содержать не менее :min символов.',
            'reason.max' => 'Причина не должна превышать :max символов.',
        ];
    }
}
