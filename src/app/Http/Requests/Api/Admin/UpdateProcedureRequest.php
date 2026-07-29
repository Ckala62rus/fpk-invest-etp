<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\TradeDirection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос обновления черновика ТЗП (super_admin | trade_admin).
 */
class UpdateProcedureRequest extends FormRequest
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
     * Правила частичного обновления черновика ТЗП.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(array_column(ProcedureType::cases(), 'value'))],
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'trade_direction' => ['sometimes', 'nullable', 'string', Rule::in(array_column(TradeDirection::cases(), 'value'))],
            'visibility' => ['sometimes', 'string', Rule::in(array_column(ProcedureVisibility::cases(), 'value'))],
            'company_id' => ['sometimes', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'classifier_category_id' => [
                'sometimes',
                'integer',
                Rule::exists('classifier_categories', 'id')->whereNull('deleted_at'),
            ],
            'responsible_user_id' => ['sometimes', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'customer_contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'storage_years' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'source_procedure_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('procedures', 'id')->whereNull('deleted_at'),
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
            'type.in' => 'Тип процедуры указан неверно.',
            'title.string' => 'Название процедуры должно быть строкой.',
            'title.max' => 'Название процедуры не должно превышать :max символов.',
            'description.string' => 'Описание процедуры должно быть строкой.',
            'trade_direction.in' => 'Направление торгов указано неверно.',
            'visibility.in' => 'Открытость процедуры указана неверно.',
            'company_id.exists' => 'Указанный заказчик не найден.',
            'classifier_category_id.exists' => 'Указанная категория классификатора не найдена.',
            'responsible_user_id.exists' => 'Указанный ответственный пользователь не найден.',
            'customer_contact_name.string' => 'ФИО заказчика должно быть строкой.',
            'customer_contact_name.max' => 'ФИО заказчика не должно превышать :max символов.',
            'customer_contact_email.email' => 'Email заказчика указан неверно.',
            'customer_contact_email.max' => 'Email заказчика не должен превышать :max символов.',
            'starts_at.date' => 'Дата начала указана неверно.',
            'ends_at.date' => 'Дата окончания указана неверно.',
            'ends_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
            'storage_years.integer' => 'Срок хранения должен быть целым числом.',
            'storage_years.min' => 'Срок хранения должен быть не меньше :min года.',
            'storage_years.max' => 'Срок хранения не должен превышать :max лет.',
            'source_procedure_id.exists' => 'Исходная процедура (КП) не найдена.',
        ];
    }
}
