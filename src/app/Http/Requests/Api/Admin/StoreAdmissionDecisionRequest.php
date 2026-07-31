<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\AdmissionDecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос решения о допуске / недопуске коммерческого предложения (КП).
 */
class StoreAdmissionDecisionRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей и контроллером.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила решения о допуске.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::in(array_column(AdmissionDecision::cases(), 'value'))],
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
            'clarification_deadline' => ['sometimes', 'nullable', 'date', 'after:now'],
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
            'decision.required' => 'Укажите решение: допуск или недопуск.',
            'decision.in' => 'Решение указано неверно (допустимы admit или reject).',
            'reason.required' => 'Укажите причину решения о допуске или недопуске.',
            'reason.string' => 'Причина должна быть строкой.',
            'reason.min' => 'Причина должна содержать не менее :min символов.',
            'reason.max' => 'Причина не должна превышать :max символов.',
            'clarification_deadline.date' => 'Срок уточнения указан неверно.',
            'clarification_deadline.after' => 'Срок уточнения должен быть в будущем.',
        ];
    }
}
