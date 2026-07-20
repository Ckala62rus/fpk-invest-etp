<?php

namespace App\Http\Requests\Api;

use App\Enums\EntityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос регистрации участника ЭТП (электронной торговой площадки).
 */
class RegistrationRequest extends FormRequest
{
    /**
     * Определяет, разрешена ли публичная регистрация.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Возвращает правила проверки данных регистрации.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inn' => ['required', 'string', 'max:12', 'unique:users,inn'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'entity_type' => ['required', Rule::enum(EntityType::class)],
            'name' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'director_name' => ['required', 'string', 'max:255'],
            'director_birth_date' => ['nullable', 'date'],
            'contact_persons' => ['required', 'string'],
            'extra_emails' => ['nullable', 'array'],
            'extra_emails.*' => ['email', 'max:255', 'distinct'],
            'pd_consent' => ['accepted'],
        ];
    }
}
