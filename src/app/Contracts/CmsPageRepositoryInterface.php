<?php

namespace App\Contracts;

use App\DTOs\CmsPageFilterDTO;
use App\Models\CmsPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория страниц CMS ЭТП.
 */
interface CmsPageRepositoryInterface
{
    /**
     * Постраничный список страниц CMS с фильтрами (админка).
     *
     * @param CmsPageFilterDTO $filter Фильтры поиска и soft delete
     * @return LengthAwarePaginator Пагинатор CmsPage
     */
    public function paginate(CmsPageFilterDTO $filter): LengthAwarePaginator;

    /**
     * Находит страницу по ID (без soft-deleted по умолчанию).
     *
     * @param int $id Идентификатор страницы
     * @return CmsPage|null
     */
    public function findById(int $id): ?CmsPage;

    /**
     * Находит опубликованную страницу по slug для публичного API.
     *
     * @param string $slug URL-slug
     * @return CmsPage|null
     */
    public function findPublishedBySlug(string $slug): ?CmsPage;

    /**
     * Список опубликованных страниц для публичного меню/футера.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CmsPage>
     */
    public function listPublished();
}
