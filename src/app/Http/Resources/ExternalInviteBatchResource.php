<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс batch внешних приглашений.
 *
 * @mixin \App\Models\ExternalInviteBatch
 */
class ExternalInviteBatchResource extends JsonResource
{
    /**
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'emails' => $this->emails,
            'duplicates_skipped' => $this->duplicates_skipped,
            'created_by' => $this->created_by,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
