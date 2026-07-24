<?php

namespace App\DTOs;

use App\Enums\UserStatus;
use App\Http\Requests\Api\Admin\ListUsersRequest;

/**
 * DTO фильтров списка пользователей для админского API ЭТП (электронной торговой площадки).
 *
 * Передаётся из FormRequest в UserRepository без «сырых» массивов запроса.
 */
readonly class UserFilterDTO
{
    /**
     * Создаёт набор фильтров пагинации и поиска пользователей.
     *
     * @param UserStatus|null $status Фильтр по статусу учётной записи
     * @param string|null $search Подстрока поиска по ИНН (идентификационный номер налогоплательщика), email или наименованию
     * @param string|null $role Имя роли Spatie Permission (например participant)
     * @param string|null $trashed Режим soft delete: without|with|only
     * @param int $perPage Размер страницы пагинации
     * @return void
     */
    public function __construct(
        public ?UserStatus $status = null,
        public ?string $search = null,
        public ?string $role = null,
        public ?string $trashed = 'without',
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса списка пользователей.
     *
     * @param ListUsersRequest $request Запрос с фильтрами и пагинацией
     * @return self
     */
    public static function fromRequest(ListUsersRequest $request): self
    {
        $status = $request->validated('status');

        return new self(
            status: is_string($status) && $status !== ''
                ? UserStatus::from($status)
                : null,
            search: $request->validated('search'),
            role: $request->validated('role'),
            trashed: $request->validated('trashed') ?? 'without',
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
