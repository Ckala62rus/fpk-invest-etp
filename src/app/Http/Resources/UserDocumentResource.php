<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс документа профиля участника ЭТП.
 *
 * @mixin \App\Models\UserDocument
 */
class UserDocumentResource extends JsonResource
{
    /**
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'valid_until' => $this->valid_until?->toDateString(),
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
        ];
    }
}
