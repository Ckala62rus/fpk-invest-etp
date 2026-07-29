<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListProceduresRequest;

/**
 * DTO фильтров админского списка ТЗП (торгово-закупочных процедур).
 */
readonly class ProcedureFilterDTO
{
    /**
     * @param string|null $search Поиск по номеру/названию
     * @param string|null $type Тип ТЗП
     * @param string|null $status Статус
     * @param string|null $visibility Открытость
     * @param int|null $companyId Заказчик
     * @param int|null $classifierCategoryId Категория классификатора
     * @param int|null $responsibleUserId Ответственный (для trade_admin — свои ТЗП)
     * @param string|null $trashed without|with|only
     * @param int $perPage Размер страницы
     * @return void
     */
    public function __construct(
        public ?string $search = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $visibility = null,
        public ?int $companyId = null,
        public ?int $classifierCategoryId = null,
        public ?int $responsibleUserId = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса.
     *
     * @param ListProceduresRequest $request Запрос с фильтрами
     * @param int|null $forceResponsibleUserId Принудительный фильтр ответственного (trade_admin)
     * @return self
     */
    public static function fromRequest(
        ListProceduresRequest $request,
        ?int $forceResponsibleUserId = null,
    ): self {
        $companyId = $request->validated('company_id');
        $categoryId = $request->validated('classifier_category_id');
        $responsibleId = $forceResponsibleUserId
            ?? $request->validated('responsible_user_id');

        return new self(
            search: $request->validated('search'),
            type: $request->validated('type'),
            status: $request->validated('status'),
            visibility: $request->validated('visibility'),
            companyId: $companyId === null ? null : (int) $companyId,
            classifierCategoryId: $categoryId === null ? null : (int) $categoryId,
            responsibleUserId: $responsibleId === null ? null : (int) $responsibleId,
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
