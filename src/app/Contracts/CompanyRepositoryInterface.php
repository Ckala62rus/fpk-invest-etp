<?php

namespace App\Contracts;

use App\DTOs\CompanyFilterDTO;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория предприятий-заказчиков.
 */
interface CompanyRepositoryInterface
{
    /**
     * @param CompanyFilterDTO $filter Фильтры списка
     * @return LengthAwarePaginator
     */
    public function paginate(CompanyFilterDTO $filter): LengthAwarePaginator;

    /**
     * @param int $id Идентификатор предприятия
     * @return Company|null
     */
    public function findById(int $id): ?Company;
}
