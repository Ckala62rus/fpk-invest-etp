<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\AssignRolesRequest;
use App\Models\User;

/**
 * DTO (Data Transfer Object) назначения ролей пользователю ЭТП (электронной торговой площадки).
 */
readonly class AssignRolesDTO
{
    /**
     * @param User $user Пользователь, которому назначают роли
     * @param User $assignedBy Главный администратор (super_admin)
     * @param list<string> $roles Итоговый набор ролей (sync, не добавление)
     * @return void
     */
    public function __construct(
        public User $user,
        public User $assignedBy,
        public array $roles,
    ) {
    }

    /**
     * Собирает DTO из маршрута, сессии и валидированного FormRequest.
     *
     * @param User $user Цель назначения ролей
     * @param User $assignedBy Администратор из текущей сессии
     * @param AssignRolesRequest $request Валидированный список roles
     * @return self
     */
    public static function fromRequest(User $user, User $assignedBy, AssignRolesRequest $request): self
    {
        /** @var list<string> $roles */
        $roles = array_values(array_unique($request->validated('roles')));

        return new self(
            user: $user,
            assignedBy: $assignedBy,
            roles: $roles,
        );
    }
}
