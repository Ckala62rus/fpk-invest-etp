<?php

namespace App\Repositories;

use App\Contracts\CompanyGroupRepositoryInterface;
use App\DTOs\CompanyGroupFilterDTO;
use App\Models\CompanyGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-репозиторий групп компаний холдинга ЭТП (электронной торговой площадки).
 */
class CompanyGroupRepository implements CompanyGroupRepositoryInterface
{
    /**
     * Возвращает постраничный список групп компаний с фильтрами.
     *
     * @param CompanyGroupFilterDTO $filter Фильтры поиска и soft delete
     * @return LengthAwarePaginator Пагинатор CompanyGroup
     */
    public function paginate(CompanyGroupFilterDTO $filter): LengthAwarePaginator
    {
        $query = CompanyGroup::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        $this->applyTrashed($query, $filter->trashed);
        $this->applySearch($query, $filter);
        $this->applyIsActive($query, $filter);

        return $query->paginate($filter->perPage);
    }

    /**
     * Находит группу компаний по ID.
     *
     * @param int $id Идентификатор группы
     * @return CompanyGroup|null
     */
    public function findById(int $id): ?CompanyGroup
    {
        return CompanyGroup::query()->find($id);
    }

    /**
     * Применяет режим учёта soft-deleted записей.
     *
     * @param Builder<CompanyGroup> $query Базовый запрос
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
     * Фильтрует по подстроке в названии группы.
     *
     * @param Builder<CompanyGroup> $query Базовый запрос
     * @param CompanyGroupFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applySearch(Builder $query, CompanyGroupFilterDTO $filter): void
    {
        if ($filter->search === null || trim($filter->search) === '') {
            return;
        }

        $query->where('name', 'ilike', '%'.trim($filter->search).'%');
    }

    /**
     * Фильтрует по флагу is_active.
     *
     * @param Builder<CompanyGroup> $query Базовый запрос
     * @param CompanyGroupFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applyIsActive(Builder $query, CompanyGroupFilterDTO $filter): void
    {
        if ($filter->isActive === null) {
            return;
        }

        $query->where('is_active', $filter->isActive);
    }
}
