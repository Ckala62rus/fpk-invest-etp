<?php

namespace App\Contracts;

use App\DTOs\ActivityLogFilterDTO;
use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория журнала аудита для админского API.
 */
interface ActivityLogRepositoryInterface
{
    /**
     * Возвращает постраничный список записей аудита с фильтрами.
     *
     * @param ActivityLogFilterDTO $filter Фильтры канала, события, акторов и дат
     * @return LengthAwarePaginator Пагинатор ActivityLog с causer
     */
    public function paginate(ActivityLogFilterDTO $filter): LengthAwarePaginator;

    /**
     * Находит запись аудита по ID с подгрузкой causer.
     *
     * @param int $id Идентификатор записи
     * @return ActivityLog|null Модель или null, если не найдена
     */
    public function findById(int $id): ?ActivityLog;
}
