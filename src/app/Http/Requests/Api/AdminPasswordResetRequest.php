<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос обращения к администратору для восстановления доступа по ИНН.
 */
class AdminPasswordResetRequest extends FormRequest
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
            'inn' => ['required', 'string', 'max:12'],
            'message' => ['nullable', 'string', 'max:5000'],
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
            'inn.required' => 'Поле ИНН обязательно для заполнения.',
            'inn.string' => 'ИНН должен быть строкой.',
            'inn.max' => 'ИНН не должен превышать :max символов.',
            'message.string' => 'Текст обращения должен быть строкой.',
            'message.max' => 'Текст обращения не должен превышать :max символов.',
        ];
    }
}
