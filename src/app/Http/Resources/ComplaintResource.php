<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс жалобы («Подать жалобу») для ответа API ЭТП.
 *
 * @mixin \App\Models\Complaint
 */
class ComplaintResource extends JsonResource
{
    /**
     * Преобразует жалобу в JSON.
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
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
