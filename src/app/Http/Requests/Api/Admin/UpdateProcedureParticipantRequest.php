<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\ParticipantStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Запрос смены статуса участника в ТЗП (допуск / отклонение).
 */
class UpdateProcedureParticipantRequest extends FormRequest
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
     * Правила смены статуса участника.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    ParticipantStatus::Admitted->value,
                    ParticipantStatus::Rejected->value,
                    ParticipantStatus::Invited->value,
                ]),
            ],
            'rejection_reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * При отклонении причина обязательна.
     *
     * @param Validator $validator Валидатор Laravel
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') === ParticipantStatus::Rejected->value) {
                $reason = trim((string) $this->input('rejection_reason', ''));
                if ($reason === '') {
                    $validator->errors()->add(
                        'rejection_reason',
                        'При отклонении участника укажите причину.',
                    );
                }
            }
        });
    }

    /**
     * Русские сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Статус участника обязателен для заполнения.',
            'status.in' => 'Статус участника указан неверно.',
            'rejection_reason.string' => 'Причина отклонения должна быть строкой.',
            'rejection_reason.max' => 'Причина отклонения не должна превышать :max символов.',
        ];
    }
}
