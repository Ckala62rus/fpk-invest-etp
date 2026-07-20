<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс пользователя ЭТП (электронной торговой площадки) для собственных данных.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Преобразует пользователя в безопасный API-формат.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inn' => $this->inn,
            'email' => $this->email,
            'status' => $this->status->value,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()),
            'profile' => new UserProfileResource($this->whenLoaded('profile')),
        ];
    }
}
