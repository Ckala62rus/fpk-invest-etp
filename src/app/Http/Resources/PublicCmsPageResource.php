<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Публичный ресурс страницы CMS (гость видит только опубликованные).
 *
 * @mixin \App\Models\CmsPage
 */
class PublicCmsPageResource extends JsonResource
{
    /**
     * Преобразует опубликованную страницу CMS в JSON для гостя.
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
            'sort_order' => $this->sort_order,
            'content_html' => $this->whenLoaded(
                'latestRevision',
                fn () => $this->latestRevision?->content_html,
            ),
        ];
    }
}
