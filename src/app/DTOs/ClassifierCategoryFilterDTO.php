<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListClassifierCategoriesRequest;

/**
 * DTO фильтров списка категорий классификатора для админского API ЭТП.
 */
readonly class ClassifierCategoryFilterDTO
{
    /**
     * @param int|null $companyGroupId Фильтр по группе компаний (1-й уровень)
     * @param string|null $search Подстрока поиска по названию
     * @param bool|null $isActive Фильтр по активности
     * @param string|null $trashed Режим soft delete: without|with|only
     * @param int $perPage Размер страницы
     * @return void
     */
    public function __construct(
        public ?int $companyGroupId = null,
        public ?string $search = null,
        public ?bool $isActive = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса.
     *
     * @param ListClassifierCategoriesRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListClassifierCategoriesRequest $request): self
    {
        $companyGroupId = $request->validated('company_group_id');
        $isActive = $request->validated('is_active');

        return new self(
            companyGroupId: $companyGroupId !== null ? (int) $companyGroupId : null,
            search: $request->validated('search'),
            isActive: $isActive === null ? null : filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
