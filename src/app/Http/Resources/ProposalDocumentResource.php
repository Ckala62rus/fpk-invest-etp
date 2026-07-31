<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс документа коммерческого предложения (КП).
 *
 * @mixin \App\Models\ProposalDocument
 */
class ProposalDocumentResource extends JsonResource
{
    /**
     * Преобразует документ заявки в JSON (без бинарного содержимого).
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'type' => $this->type,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
