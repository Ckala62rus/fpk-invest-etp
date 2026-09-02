<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\NotificationEventType;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос обновления шаблона email-уведомления.
 */
class UpdateNotificationTemplateRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var NotificationTemplate $template */
        $template = $this->route('notificationTemplate');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique(NotificationTemplate::class, 'code')->ignore($template->id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:500'],
            'body_html' => ['sometimes', 'string', 'max:50000'],
            'event_type' => ['sometimes', 'nullable', 'string', Rule::in(array_column(NotificationEventType::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.string' => 'Код шаблона должен быть строкой.',
            'code.max' => 'Код шаблона не должен превышать :max символов.',
            'code.regex' => 'Код шаблона: только латиница, цифры и подчёркивание.',
            'code.unique' => 'Шаблон с таким кодом уже существует.',
            'name.string' => 'Название должно быть строкой.',
            'name.max' => 'Название не должно превышать :max символов.',
            'subject.string' => 'Тема должна быть строкой.',
            'subject.max' => 'Тема не должна превышать :max символов.',
            'body_html.string' => 'Тело письма должно быть строкой.',
            'body_html.max' => 'Тело письма слишком большое.',
            'event_type.in' => 'Тип события указан неверно.',
            'is_active.boolean' => 'Признак активности должен быть логическим значением.',
        ];
    }
}
