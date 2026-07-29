<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос сообщения о коррупции — гость или авторизованный пользователь.
 */
class StoreCorruptionReportRequest extends FormRequest
{
    /**
     * Доступен без auth; при сессии user_id подставится в контроллере.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила подачи сообщения о коррупции.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isGuest = $this->user() === null;

        return [
            'name' => [$isGuest ? 'required' : 'sometimes', 'nullable', 'string', 'max:255'],
            'email' => [$isGuest ? 'required' : 'sometimes', 'nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
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
            'name.required' => 'Имя заявителя обязательно для заполнения.',
            'name.string' => 'Имя заявителя должно быть строкой.',
            'name.max' => 'Имя заявителя не должно превышать :max символов.',
            'email.required' => 'Email заявителя обязателен для заполнения.',
            'email.email' => 'Email заявителя указан неверно.',
            'email.max' => 'Email заявителя не должен превышать :max символов.',
            'message.required' => 'Текст сообщения обязателен для заполнения.',
            'message.string' => 'Текст сообщения должен быть строкой.',
            'message.max' => 'Текст сообщения не должен превышать :max символов.',
        ];
    }
}
