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
            'inn.unique' => 'Пользователь с таким ИНН уже зарегистрирован.',

            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Укажите корректный адрес email.',
            'email.max' => 'Email не должен превышать :max символов.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',

            'password.required' => 'Поле пароля обязательно для заполнения.',
            'password.string' => 'Пароль должен быть строкой.',
            'password.min' => 'Пароль должен содержать минимум :min символов.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',

            'entity_type.required' => 'Укажите тип субъекта (юрлицо или физлицо).',
            'entity_type.enum' => 'Некорректный тип субъекта.',

            'name.required' => 'Укажите наименование организации или ФИО.',
            'name.string' => 'Наименование должно быть строкой.',
            'name.max' => 'Наименование не должно превышать :max символов.',

            'phone.required' => 'Поле телефона обязательно для заполнения.',
            'phone.string' => 'Телефон должен быть строкой.',
            'phone.max' => 'Телефон не должен превышать :max символов.',

            'director_name.required' => 'Укажите ФИО руководителя.',
            'director_name.string' => 'ФИО руководителя должно быть строкой.',
            'director_name.max' => 'ФИО руководителя не должно превышать :max символов.',

            'director_birth_date.date' => 'Дата рождения руководителя должна быть корректной датой.',

            'contact_persons.required' => 'Укажите контактные лица.',
            'contact_persons.string' => 'Контактные лица должны быть текстом.',

            'extra_emails.array' => 'Дополнительные email должны быть списком.',
            'extra_emails.*.email' => 'Каждый дополнительный адрес должен быть корректным email.',
            'extra_emails.*.max' => 'Дополнительный email не должен превышать :max символов.',
            'extra_emails.*.distinct' => 'Дополнительные email не должны повторяться.',

            'pd_consent.accepted' => 'Необходимо согласие на обработку персональных данных.',
        ];
    }
}
