<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "inn" => ["required", "string"],
            "password" => ["required", "string"],
        ];
    }

    /**
     * Сообщения об ошибках для валидации
     */
    public function messages(): array
    {
        return [
            // INN
            'inn.required' => 'Поле ИНН обязательно для заполнения.',
            'inn.string' => 'ИНН должен быть строкой.',

            // PASSWORD
            'password.required' => 'Поле пароля обязательно для заполнения.',
            'password.string' => 'Пароль должен быть строкой.',
        ];
    }
}
