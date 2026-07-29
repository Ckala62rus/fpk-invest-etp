<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс ревизии страницы CMS для админского API ЭТП.
 *
 * @mixin \App\Models\CmsPageRevision
 */
class CmsPageRevisionResource extends JsonResource
{
    /**
     * Преобразует ревизию в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'content_html' => $this->content_html,
            'revised_by' => $this->revised_by,
            'revised_by_user' => $this->whenLoaded('revisedBy', function () {
                return [
                    'id' => $this->revisedBy->id,
                    'inn' => $this->revisedBy->inn,
                    'email' => $this->revisedBy->email,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
