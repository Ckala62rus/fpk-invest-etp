<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Метаданные PDF-протокола аукциона для админки.
 *
 * @mixin \App\Models\AuctionProtocol
 */
class AuctionProtocolResource extends JsonResource
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
            'file_path' => $this->file_path,
            'generated_by' => $this->generated_by,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'template_version' => $this->template_version,
        ];
    }
}
