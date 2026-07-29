<?php

namespace App\Repositories;

use App\Contracts\CmsPageRepositoryInterface;
use App\DTOs\CmsPageFilterDTO;
use App\Models\CmsPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent-репозиторий страниц CMS ЭТП (электронной торговой площадки).
 */
class CmsPageRepository implements CmsPageRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function paginate(CmsPageFilterDTO $filter): LengthAwarePaginator
    {
        $query = CmsPage::query()
            ->with(['latestRevision'])
            ->orderBy('sort_order')
            ->orderBy('id');

        $this->applyTrashed($query, $filter->trashed);
        $this->applySearch($query, $filter);
        $this->applyIsPublished($query, $filter);

        return $query->paginate($filter->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?CmsPage
    {
        return CmsPage::query()
            ->with(['latestRevision.revisedBy', 'revisions' => static function ($q): void {
                $q->orderByDesc('id')->with('revisedBy');
            }])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findPublishedBySlug(string $slug): ?CmsPage
    {
        return CmsPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('latestRevision')
            ->first();
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection<int, CmsPage>
     */
    public function listPublished(): Collection
    {
        return CmsPage::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'title', 'sort_order', 'meta_title', 'meta_description']);
    }

    /**
     * Применяет режим учёта soft-deleted записей.
     *
     * @param Builder<CmsPage> $query Базовый запрос
     * @param string|null $trashed without|with|only
     * @return void
     */
    private function applyTrashed(Builder $query, ?string $trashed): void
    {
        match ($trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };
    }

    /**
     * Фильтрует по подстроке в slug или title.
     *
     * @param Builder<CmsPage> $query Базовый запрос
     * @param CmsPageFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applySearch(Builder $query, CmsPageFilterDTO $filter): void
    {
        if ($filter->search === null || trim($filter->search) === '') {
            return;
        }

        $term = '%'.trim($filter->search).'%';

        $query->where(static function (Builder $q) use ($term): void {
            $q->where('slug', 'ilike', $term)
                ->orWhere('title', 'ilike', $term);
        });
    }

    /**
     * Фильтрует по флагу публикации.
     *
     * @param Builder<CmsPage> $query Базовый запрос
     * @param CmsPageFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applyIsPublished(Builder $query, CmsPageFilterDTO $filter): void
    {
        if ($filter->isPublished === null) {
            return;
        }

        $query->where('is_published', $filter->isPublished);
    }
}
