<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос приглашения участника в ТЗП (закрытые процедуры / предварительный список).
 */
class StoreProcedureParticipantRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила приглашения: только роль participant, активная учётная запись.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
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
            'user_id.required' => 'Идентификатор участника обязателен для заполнения.',
            'user_id.integer' => 'Идентификатор участника должен быть целым числом.',
            'user_id.exists' => 'Указанный пользователь не найден.',
        ];
    }
}
