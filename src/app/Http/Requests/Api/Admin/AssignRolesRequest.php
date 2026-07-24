<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос назначения ролей пользователю (только super_admin).
 *
 * Роль `guest` не назначается учётным записям — это роль неаутентифицированного доступа.
 */
class AssignRolesRequest extends FormRequest
{
    /**
     * Роли, допустимые для назначения через API.
     *
     * @var list<string>
     */
    public const ASSIGNABLE_ROLES = [
        'super_admin',
        'trade_admin',
        'auditor',
        'participant',
    ];

    /**
     * Доступ контролируется middleware `role:super_admin`.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила тела запроса назначения ролей.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(self::ASSIGNABLE_ROLES)],
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
            'roles.required' => 'Укажите хотя бы одну роль.',
            'roles.array' => 'Роли должны быть переданы массивом.',
            'roles.min' => 'Укажите хотя бы одну роль.',
            'roles.*.required' => 'Имя роли обязательно.',
            'roles.*.string' => 'Имя роли должно быть строкой.',
            'roles.*.in' => 'Указана недопустимая роль. Допустимы: super_admin, trade_admin, auditor, participant.',
        ];
    }
}
