<?php

namespace App\Actions\Admin;

use App\DTOs\AssignRolesDTO;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Действие назначения ролей RBAC (role-based access control) пользователю ЭТП.
 *
 * Полностью заменяет набор ролей через syncRoles (не добавляет поверх).
 * Доступно только главному администратору (super_admin).
 */
class AssignRolesAction
{
    /**
     * Синхронизирует роли пользователя с переданным списком.
     *
     * @param AssignRolesDTO $dto Пользователь, администратор и итоговые роли
     * @return User Пользователь с обновлёнными ролями
     *
     * @throws DomainException Если операция запрещена доменными правилами
     */
    public function execute(AssignRolesDTO $dto): User
    {
        $this->assertCanAssign($dto);

        return DB::transaction(function () use ($dto): User {
            $user = $dto->user;
            $previousRoles = $user->getRoleNames()->values()->all();

            $user->syncRoles($dto->roles);

            activity('user')
                ->causedBy($dto->assignedBy)
                ->performedOn($user)
                ->event('roles_assigned')
                ->withProperties([
                    'roles_before' => $previousRoles,
                    'roles_after' => $dto->roles,
                ])
                ->log('Назначены роли пользователя');

            return $user->refresh()->load(['roles', 'profile']);
        });
    }

    /**
     * Проверяет доменные ограничения на смену ролей.
     *
     * @param AssignRolesDTO $dto Данные операции
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanAssign(AssignRolesDTO $dto): void
    {
        if ($dto->user->is($dto->assignedBy)) {
            throw new DomainException(
                message: 'Нельзя изменять роли собственной учётной записи.',
                statusCode: 422,
            );
        }
    }
}
