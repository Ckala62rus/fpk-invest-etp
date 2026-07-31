<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос сообщения в переписке по уточнению КП.
 */
class StoreProposalMessageRequest extends FormRequest
{
    /**
     * Доступ проверяется в Action / контроллере.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила сообщения и опционального запроса уточнения.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:10000'],
            'attachments' => ['sometimes', 'nullable', 'array', 'max:20'],
            'attachments.*' => ['required', 'string', 'max:500'],
            'request_clarification' => ['sometimes', 'boolean'],
            'clarification_deadline' => ['required_if:request_clarification,true', 'nullable', 'date', 'after:now'],
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
            'message.required' => 'Текст сообщения обязателен для заполнения.',
            'message.string' => 'Текст сообщения должен быть строкой.',
            'message.min' => 'Текст сообщения не может быть пустым.',
            'message.max' => 'Текст сообщения не должен превышать :max символов.',
            'attachments.array' => 'Вложения должны быть массивом.',
            'attachments.max' => 'Не более :max вложений.',
            'attachments.*.required' => 'Вложение не может быть пустым.',
            'attachments.*.string' => 'Вложение должно быть строкой.',
            'attachments.*.max' => 'Путь вложения не должен превышать :max символов.',
            'request_clarification.boolean' => 'Признак запроса уточнения должен быть логическим значением.',
            'clarification_deadline.required_if' => 'Укажите срок уточнения коммерческого предложения.',
            'clarification_deadline.date' => 'Срок уточнения указан неверно.',
            'clarification_deadline.after' => 'Срок уточнения должен быть в будущем.',
        ];
    }
}
