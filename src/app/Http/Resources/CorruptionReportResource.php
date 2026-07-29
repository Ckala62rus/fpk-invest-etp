<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс сообщения о коррупции для ответа API ЭТП.
 *
 * @mixin \App\Models\CorruptionReport
 */
class CorruptionReportResource extends JsonResource
{
    /**
     * Преобразует сообщение о коррупции в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
