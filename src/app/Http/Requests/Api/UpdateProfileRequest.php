<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос обновления собственного профиля пользователя ЭТП.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Обновлять профиль может только аутентифицированный пользователь (маршрут под auth).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['sometimes', 'in:legal,individual'],
            'name' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'director_name' => ['sometimes', 'string', 'max:255'],
            'director_birth_date' => ['nullable', 'date'],
            'contact_persons' => ['sometimes', 'string'],
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
            'entity_type.in' => 'Тип субъекта должен быть legal (юрлицо) или individual (физлицо).',
            'name.string' => 'Наименование должно быть строкой.',
            'name.max' => 'Наименование не должно превышать :max символов.',
            'phone.string' => 'Телефон должен быть строкой.',
            'phone.max' => 'Телефон не должен превышать :max символов.',
            'director_name.string' => 'ФИО руководителя должно быть строкой.',
            'director_name.max' => 'ФИО руководителя не должно превышать :max символов.',
            'director_birth_date.date' => 'Дата рождения руководителя должна быть корректной датой.',
            'contact_persons.string' => 'Контактные лица должны быть текстом.',
        ];
    }
}
