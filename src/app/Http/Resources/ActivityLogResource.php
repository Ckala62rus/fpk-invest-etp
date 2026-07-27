<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс записи аудита для админского API ЭТП (электронной торговой площадки).
 *
 * @mixin \App\Models\ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Преобразует запись аудита в JSON для списка и карточки.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'properties' => $this->properties,
            'batch_uuid' => $this->batch_uuid,
            'created_at' => $this->created_at?->toIso8601String(),
            'causer' => $this->when(
                $this->relationLoaded('causer') && $this->causer instanceof User,
                fn () => [
                    'id' => $this->causer->id,
                    'inn' => $this->causer->inn,
                    'email' => $this->causer->email,
                ],
            ),
        ];
    }
}
