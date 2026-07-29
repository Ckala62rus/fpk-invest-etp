<?php

namespace App\DTOs;

use App\Http\Requests\Api\Public\ListPublicProceduresRequest;

/**
 * DTO фильтров публичного списка ТЗП (торгово-закупочных процедур) для гостя.
 */
readonly class PublicProcedureFilterDTO
{
    /**
     * @param string|null $search Поиск по номеру/названию
     * @param string|null $type Тип ТЗП (request_for_proposal|auction)
     * @param string|null $status Статус процедуры
     * @param int|null $companyId Заказчик
     * @param int|null $classifierCategoryId Категория классификатора
     * @param int $perPage Размер страницы
     * @return void
     */
    public function __construct(
        public ?string $search = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?int $companyId = null,
        public ?int $classifierCategoryId = null,
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного публичного запроса.
     *
     * @param ListPublicProceduresRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListPublicProceduresRequest $request): self
    {
        $companyId = $request->validated('company_id');
        $categoryId = $request->validated('classifier_category_id');

        return new self(
            search: $request->validated('search'),
            type: $request->validated('type'),
            status: $request->validated('status'),
            companyId: $companyId === null ? null : (int) $companyId,
            classifierCategoryId: $categoryId === null ? null : (int) $categoryId,
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
