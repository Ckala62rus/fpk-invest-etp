<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос на отправку инструкции восстановления пароля по email.
 */
class ForgotPasswordRequest extends FormRequest
{
    /**
     * Запрос доступен без авторизации.
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
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Русские сообщения об ошибках валидации для фронтенда.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Укажите корректный адрес email.',
        ];
    }
}
