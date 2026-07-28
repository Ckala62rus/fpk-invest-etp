<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс настроек email-оповещений пользователя ЭТП.
 *
 * @mixin \App\Models\UserNotificationSetting
 */
class UserNotificationSettingResource extends JsonResource
{
    /**
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'all_disabled' => $this->all_disabled,
            'notify_new_auctions' => $this->notify_new_auctions,
            'notify_new_procedures' => $this->notify_new_procedures,
            'notify_day_before' => $this->notify_day_before,
            'notify_hour_before' => $this->notify_hour_before,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
