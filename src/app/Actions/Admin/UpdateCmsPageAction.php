<?php

namespace App\Actions\Admin;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Обновляет метаданные страницы CMS и при необходимости создаёт новую ревизию.
 *
 * Слой Action: атомарная операция админки (фаза 4.1).
 */
class UpdateCmsPageAction
{
    /**
     * Обновляет страницу CMS; при передаче content_html — пишет новую ревизию.
     *
     * @param CmsPage $page Целевая страница
     * @param array{
     *     slug?: string,
     *     title?: string,
     *     meta_title?: string|null,
     *     meta_description?: string|null,
     *     is_published?: bool,
     *     sort_order?: int,
     *     content_html?: string
     * } $data Валидированные поля (частичное обновление)
     * @param User $author Администратор, создающий ревизию
     * @return CmsPage Обновлённая страница с latestRevision
     */
    public function execute(CmsPage $page, array $data, User $author): CmsPage
    {
        return DB::transaction(function () use ($page, $data, $author): CmsPage {
            $meta = collect($data)->except('content_html')->all();

            if ($meta !== []) {
                $page->update($meta);
            }

            if (array_key_exists('content_html', $data) && $data['content_html'] !== null) {
                CmsPageRevision::query()->create([
                    'page_id' => $page->id,
                    'content_html' => $data['content_html'],
                    'revised_by' => $author->id,
                ]);
            }

            return $page->refresh()->load('latestRevision.revisedBy');
        });
    }
}
