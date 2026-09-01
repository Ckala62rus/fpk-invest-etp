<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос массовой рассылки внешних приглашений (фаза 6.8).
 */
class StoreExternalInviteRequest extends FormRequest
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
            'emails' => ['required', 'array', 'min:1', 'max:500'],
            'emails.*' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emails.required' => 'Укажите список email для приглашения.',
            'emails.array' => 'Список email должен быть массивом.',
            'emails.min' => 'Укажите хотя бы один email.',
            'emails.max' => 'Не более :max адресов за одну рассылку.',
            'emails.*.required' => 'Email не может быть пустым.',
            'emails.*.email' => 'Email указан неверно.',
            'emails.*.max' => 'Email не должен превышать :max символов.',
        ];
    }
}
