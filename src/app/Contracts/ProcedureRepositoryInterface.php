<?php

namespace App\Contracts;

use App\DTOs\ProcedureFilterDTO;
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

    /**
     * Админский постраничный список ТЗП с фильтрами.
     *
     * @param ProcedureFilterDTO $filter Фильтры (в т.ч. responsible_user_id для trade_admin)
     * @return LengthAwarePaginator Пагинатор Procedure
     */
    public function paginateAdmin(ProcedureFilterDTO $filter): LengthAwarePaginator;

    /**
     * Админская карточка ТЗП по ID (со связями).
     *
     * @param int $id Идентификатор процедуры
     * @return Procedure|null
     */
    public function findAdminById(int $id): ?Procedure;
}
