<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос списка пользователей в админке ЭТП (электронной торговой площадки).
 *
 * Query-параметры: status, search, role, trashed, per_page.
 * Авторизация маршрута — middleware ролей (super_admin|trade_admin|auditor).
 */
class ListUsersRequest extends FormRequest
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
     * Нормализует пустые query-параметры в null до валидации.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nullable = ['status', 'search', 'role', 'trashed', 'per_page'];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Правила фильтров и пагинации списка пользователей.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::enum(UserStatus::class)],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'max:64'],
            'trashed' => ['sometimes', 'nullable', 'string', Rule::in(['without', 'with', 'only'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Русские сообщения об ошибках валидации фильтров.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.string' => 'Статус должен быть строкой.',
            'status.enum' => 'Указан недопустимый статус пользователя.',
            'search.string' => 'Строка поиска должна быть текстом.',
            'search.max' => 'Строка поиска не должна превышать :max символов.',
            'role.string' => 'Роль должна быть строкой.',
            'role.max' => 'Имя роли не должно превышать :max символов.',
            'trashed.string' => 'Параметр trashed должен быть строкой.',
            'trashed.in' => 'Параметр trashed должен быть одним из: without, with, only.',
            'per_page.integer' => 'Количество записей на странице должно быть целым числом.',
            'per_page.min' => 'Количество записей на странице должно быть не меньше :min.',
            'per_page.max' => 'Количество записей на странице не должно превышать :max.',
        ];
    }
}
