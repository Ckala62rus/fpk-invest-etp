<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос смены пароля по одноразовому токену восстановления.
 */
class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'token.required' => 'Токен восстановления обязателен.',
            'token.string' => 'Токен восстановления должен быть строкой.',
            'password.required' => 'Поле пароля обязательно для заполнения.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль должен содержать минимум :min символов.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',
        ];
    }
}
