<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос блокировки пользователя администратором ЭТП (электронной торговой площадки).
 */
class BlockUserRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware `role`, здесь всегда разрешено.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила тела запроса блокировки.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'blocked_until' => ['sometimes', 'nullable', 'date', 'after:now'],
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
            'reason.required' => 'Укажите причину блокировки.',
            'reason.string' => 'Причина блокировки должна быть строкой.',
            'reason.min' => 'Причина блокировки должна содержать не менее :min символов.',
            'reason.max' => 'Причина блокировки не должна превышать :max символов.',
            'blocked_until.date' => 'Дата окончания блокировки должна быть корректной датой.',
            'blocked_until.after' => 'Дата окончания блокировки должна быть в будущем.',
        ];
    }
}
