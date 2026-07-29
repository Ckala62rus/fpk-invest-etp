<?php

namespace App\Actions\Admin;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Создаёт страницу CMS и первую ревизию содержимого.
 *
 * Слой Action: атомарная операция админки (фаза 4.1).
 */
class CreateCmsPageAction
{
    /**
     * Создаёт страницу CMS с первой версией HTML-контента.
     *
     * @param array{
     *     slug: string,
     *     title: string,
     *     meta_title?: string|null,
     *     meta_description?: string|null,
     *     is_published?: bool,
     *     sort_order?: int,
     *     content_html: string
     * } $data Валидированные поля страницы и контента
     * @param User $author Администратор, создающий ревизию
     * @return CmsPage Созданная страница с latestRevision
     */
    public function execute(array $data, User $author): CmsPage
    {
        return DB::transaction(function () use ($data, $author): CmsPage {
            $page = CmsPage::query()->create([
                'slug' => $data['slug'],
                'title' => $data['title'],
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_published' => $data['is_published'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            CmsPageRevision::query()->create([
                'page_id' => $page->id,
                'content_html' => $data['content_html'],
                'revised_by' => $author->id,
            ]);

            return $page->load('latestRevision.revisedBy');
        });
    }
}
