<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Сохранение служебного комментария о пользователе в карточке админки.
 */
class UpdateUserAdminNotesRequest extends FormRequest
{
    /**
     * Доступ — middleware ролей (super_admin, trade_admin).
     *
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
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_notes.string' => 'Комментарий должен быть строкой.',
            'admin_notes.max' => 'Комментарий не должен превышать :max символов.',
        ];
    }
}
