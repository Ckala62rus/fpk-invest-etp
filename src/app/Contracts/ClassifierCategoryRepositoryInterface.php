<?php

namespace App\Contracts;

use App\DTOs\ClassifierCategoryFilterDTO;
use App\Models\ClassifierCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория категорий классификатора (2-й уровень).
 */
interface ClassifierCategoryRepositoryInterface
{
    /**
     * @param ClassifierCategoryFilterDTO $filter Фильтры списка
     * @return LengthAwarePaginator
     */
    public function paginate(ClassifierCategoryFilterDTO $filter): LengthAwarePaginator;

    /**
     * @param int $id Идентификатор категории
     * @return ClassifierCategory|null
     */
    public function findById(int $id): ?ClassifierCategory;
}
