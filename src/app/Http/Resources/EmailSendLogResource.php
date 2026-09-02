<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс записи журнала отправки email.
 *
 * @mixin \App\Models\EmailSendLog
 */
class EmailSendLogResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'template_code' => $this->whenLoaded('template', fn () => $this->template?->code),
            'recipient_email' => $this->recipient_email,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'error' => $this->error,
            'payload' => $this->payload,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
