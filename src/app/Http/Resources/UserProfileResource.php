<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс профиля пользователя ЭТП (электронной торговой площадки).
 *
 * @mixin \App\Models\UserProfile
 */
class UserProfileResource extends JsonResource
{
    /**
     * Преобразует профиль в безопасный API-формат.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity_type' => $this->entity_type->value,
            'name' => $this->name,
            'phone' => $this->phone,
            'director_name' => $this->director_name,
            'director_birth_date' => $this->director_birth_date?->toDateString(),
            'contact_persons' => $this->contact_persons,
        ];
    }
}
