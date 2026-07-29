<?php

namespace App\Contracts;

use App\DTOs\PublicProcedureFilterDTO;
use App\Models\Procedure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория ТЗП (торгово-закупочных процедур).
 */
interface ProcedureRepositoryInterface
{
    /**
     * Публичный список открытых ТЗП для гостя (без черновиков и закрытых).
     *
     * @param PublicProcedureFilterDTO $filter Фильтры поиска
     * @return LengthAwarePaginator Пагинатор Procedure
     */
    public function paginatePublic(PublicProcedureFilterDTO $filter): LengthAwarePaginator;

    /**
     * Публичная карточка открытой ТЗП по ID.
     *
     * @param int $id Идентификатор процедуры
     * @return Procedure|null
     */
    public function findPublicById(int $id): ?Procedure;
}
