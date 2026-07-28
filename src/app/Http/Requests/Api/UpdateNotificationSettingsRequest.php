<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос обновления настроек email-оповещений участника ЭТП.
 */
class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all_disabled' => ['sometimes', 'boolean'],
            'notify_new_auctions' => ['sometimes', 'boolean'],
            'notify_new_procedures' => ['sometimes', 'boolean'],
            'notify_day_before' => ['sometimes', 'boolean'],
            'notify_hour_before' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'all_disabled.boolean' => 'Поле отписки от всех рассылок должно быть логическим значением.',
            'notify_new_auctions.boolean' => 'Поле оповещения о новых аукционах должно быть логическим значением.',
            'notify_new_procedures.boolean' => 'Поле оповещения о новых ТЗП должно быть логическим значением.',
            'notify_day_before.boolean' => 'Поле напоминания за день должно быть логическим значением.',
            'notify_hour_before.boolean' => 'Поле напоминания за час должно быть логическим значением.',
        ];
    }
}
