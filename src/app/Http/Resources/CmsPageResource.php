<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс страницы CMS для админского API ЭТП.
 *
 * @mixin \App\Models\CmsPage
 */
class CmsPageResource extends JsonResource
{
    /**
     * Преобразует страницу CMS в JSON (с актуальной ревизией и историей при загрузке).
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'content_html' => $this->whenLoaded(
                'latestRevision',
                fn () => $this->latestRevision?->content_html,
            ),
            'latest_revision' => $this->whenLoaded(
                'latestRevision',
                fn () => $this->latestRevision
                    ? (new CmsPageRevisionResource($this->latestRevision))->resolve()
                    : null,
            ),
            'revisions' => $this->whenLoaded(
                'revisions',
                fn () => CmsPageRevisionResource::collection($this->revisions),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
