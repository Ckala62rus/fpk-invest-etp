<?php

namespace App\Http\Requests\Api\Admin;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\WinnerMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос обновления настроек электронного аукциона (фаза 8.1).
 */
class UpdateAuctionSettingRequest extends FormRequest
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
     * Правила частичного обновления настроек аукциона.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bid_mode' => ['sometimes', 'string', Rule::in(array_column(BidMode::cases(), 'value'))],
            'auction_mode' => ['sometimes', 'string', Rule::in(array_column(AuctionMode::cases(), 'value'))],
            'extension_minutes' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'extension_trigger_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
            'idle_timeout_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'forbid_equal_bids' => ['sometimes', 'boolean'],
            'winner_mode' => ['sometimes', 'string', Rule::in(array_column(WinnerMode::cases(), 'value'))],
            'only_admitted_from_rfp' => ['sometimes', 'boolean'],
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
            'bid_mode.string' => 'Режим ставки должен быть строкой.',
            'bid_mode.in' => 'Режим ставки указан неверно.',
            'auction_mode.string' => 'Направление аукциона должно быть строкой.',
            'auction_mode.in' => 'Направление аукциона указано неверно.',
            'extension_minutes.integer' => 'Продление должно быть целым числом минут.',
            'extension_minutes.min' => 'Продление должно быть не меньше :min мин.',
            'extension_minutes.max' => 'Продление не должно превышать :max мин.',
            'extension_trigger_minutes.integer' => 'Порог продления должен быть целым числом минут.',
            'extension_trigger_minutes.min' => 'Порог продления должен быть не меньше :min мин.',
            'extension_trigger_minutes.max' => 'Порог продления не должен превышать :max мин.',
            'idle_timeout_minutes.integer' => 'Таймаут простоя должен быть целым числом минут.',
            'idle_timeout_minutes.min' => 'Таймаут простоя должен быть не меньше :min мин.',
            'idle_timeout_minutes.max' => 'Таймаут простоя не должен превышать :max мин.',
            'forbid_equal_bids.boolean' => 'Запрет одинаковых ставок должен быть логическим значением.',
            'winner_mode.string' => 'Способ определения победителя должен быть строкой.',
            'winner_mode.in' => 'Способ определения победителя указан неверно.',
            'only_admitted_from_rfp.boolean' => 'Признак допуска с 1-го этапа должен быть логическим значением.',
        ];
    }
}
