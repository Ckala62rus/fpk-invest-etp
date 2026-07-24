<?php

namespace App\Contracts;

use App\DTOs\UserFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория пользователей для сложных выборок админского API.
 */
interface UserRepositoryInterface
{
    /**
     * Возвращает постраничный список пользователей с фильтрами.
     *
     * @param UserFilterDTO $filter Фильтры статуса, поиска, роли и soft delete
     * @return LengthAwarePaginator Пагинатор моделей User с подгруженными roles и profile
     */
    public function paginate(UserFilterDTO $filter): LengthAwarePaginator;
}
