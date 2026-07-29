<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListCmsPagesRequest;

/**
 * DTO фильтров списка страниц CMS для админского API ЭТП.
 */
readonly class CmsPageFilterDTO
{
    /**
     * @param string|null $search Подстрока поиска по slug/title
     * @param bool|null $isPublished Фильтр публикации
     * @param string|null $trashed Режим soft delete: without|with|only
     * @param int $perPage Размер страницы пагинации
     * @return void
     */
    public function __construct(
        public ?string $search = null,
        public ?bool $isPublished = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса.
     *
     * @param ListCmsPagesRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListCmsPagesRequest $request): self
    {
        $isPublished = $request->validated('is_published');

        return new self(
            search: $request->validated('search'),
            isPublished: $isPublished === null
                ? null
                : filter_var($isPublished, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
