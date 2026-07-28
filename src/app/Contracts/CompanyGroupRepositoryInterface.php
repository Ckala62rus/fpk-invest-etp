<?php

namespace App\Contracts;

use App\DTOs\CompanyGroupFilterDTO;
use App\Models\CompanyGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория групп компаний холдинга.
 */
interface CompanyGroupRepositoryInterface
{
    /**
     * Возвращает постраничный список групп компаний с фильтрами.
     *
     * @param CompanyGroupFilterDTO $filter Фильтры поиска и soft delete
     * @return LengthAwarePaginator Пагинатор CompanyGroup
     */
    public function paginate(CompanyGroupFilterDTO $filter): LengthAwarePaginator;

    /**
     * Находит группу компаний по ID.
     *
     * @param int $id Идентификатор группы
     * @return CompanyGroup|null Модель или null
     */
    public function findById(int $id): ?CompanyGroup;
}
