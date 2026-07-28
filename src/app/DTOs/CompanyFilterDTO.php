<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListCompaniesRequest;

/**
 * DTO фильтров списка предприятий-заказчиков для админского API ЭТП.
 */
readonly class CompanyFilterDTO
{
    /**
     * @param int|null $companyGroupId Фильтр по группе компаний
     * @param string|null $search Поиск по названию или ИНН
     * @param bool|null $isActive Фильтр по активности
     * @param bool|null $isExternal Фильтр внешних заказчиков
     * @param string|null $trashed Режим soft delete
     * @param int $perPage Размер страницы
     * @return void
     */
    public function __construct(
        public ?int $companyGroupId = null,
        public ?string $search = null,
        public ?bool $isActive = null,
        public ?bool $isExternal = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса.
     *
     * @param ListCompaniesRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListCompaniesRequest $request): self
    {
        $companyGroupId = $request->validated('company_group_id');
        $isActive = $request->validated('is_active');
        $isExternal = $request->validated('is_external');

        return new self(
            companyGroupId: $companyGroupId !== null ? (int) $companyGroupId : null,
            search: $request->validated('search'),
            isActive: $isActive === null ? null : filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            isExternal: $isExternal === null ? null : filter_var($isExternal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
