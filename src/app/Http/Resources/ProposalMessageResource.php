<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс сообщения переписки по уточнению КП.
 *
 * @mixin \App\Models\ProposalMessage
 */
class ProposalMessageResource extends JsonResource
{
    /**
     * Преобразует сообщение в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'sender_id' => $this->sender_id,
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => $this->sender->id,
                    'inn' => $this->sender->inn,
                    'email' => $this->sender->email,
                ];
            }),
            'message' => $this->message,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
