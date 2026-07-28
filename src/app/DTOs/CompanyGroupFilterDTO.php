<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListCompanyGroupsRequest;

/**
 * DTO фильтров списка групп компаний холдинга для админского API ЭТП.
 */
readonly class CompanyGroupFilterDTO
{
    /**
     * @param string|null $search Подстрока поиска по названию группы
     * @param bool|null $isActive Фильтр по активности
     * @param string|null $trashed Режим soft delete: without|with|only
     * @param int $perPage Размер страницы пагинации
     * @return void
     */
    public function __construct(
        public ?string $search = null,
        public ?bool $isActive = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса.
     *
     * @param ListCompanyGroupsRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListCompanyGroupsRequest $request): self
    {
        $isActive = $request->validated('is_active');

        return new self(
            search: $request->validated('search'),
            isActive: $isActive === null ? null : filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
