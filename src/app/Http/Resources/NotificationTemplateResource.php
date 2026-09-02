<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс шаблона email-уведомления.
 *
 * @mixin \App\Models\NotificationTemplate
 */
class NotificationTemplateResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'event_type' => $this->event_type?->value,
            'event_type_label' => $this->event_type?->label(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
