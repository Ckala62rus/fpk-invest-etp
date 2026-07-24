<?php

namespace App\Actions\Admin;

use App\DTOs\BlockUserDTO;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Действие блокировки учётной записи участника ЭТП (электронной торговой площадки).
 *
 * Устанавливает статус `blocked`, причину и срок; пишет запись в activity log.
 */
class BlockUserAction
{
    /**
     * Блокирует пользователя от имени администратора.
     *
     * @param BlockUserDTO $dto Цель, администратор, причина и срок
     * @return User Заблокированная учётная запись
     *
     * @throws DomainException Если нельзя заблокировать (себя, уже заблокирован, super_admin)
     */
    public function execute(BlockUserDTO $dto): User
    {
        $this->assertCanBlock($dto);

        return DB::transaction(function () use ($dto): User {
            $user = $dto->user;

            $user->disableLogging();
            $user->update([
                'status' => UserStatus::Blocked,
                'block_reason' => $dto->reason,
                'blocked_until' => $dto->blockedUntil,
            ]);
            $user->enableLogging();

            activity('user')
                ->causedBy($dto->blockedBy)
                ->performedOn($user)
                ->event('blocked')
                ->withProperties([
                    'block_reason' => $dto->reason,
                    'blocked_until' => $dto->blockedUntil?->toIso8601String(),
                ])
                ->log('Пользователь заблокирован');

            return $user->refresh()->load(['roles', 'profile']);
        });
    }

    /**
     * Проверяет доменные ограничения на блокировку.
     *
     * @param BlockUserDTO $dto Данные операции
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanBlock(BlockUserDTO $dto): void
    {
        if ($dto->user->is($dto->blockedBy)) {
            throw new DomainException(
                message: 'Нельзя заблокировать собственную учётную запись.',
                statusCode: 422,
            );
        }

        if ($dto->user->status === UserStatus::Blocked) {
            throw new DomainException(
                message: 'Пользователь уже заблокирован.',
                statusCode: 422,
            );
        }

        if ($dto->user->hasRole('super_admin') && !$dto->blockedBy->hasRole('super_admin')) {
            throw new DomainException(
                message: 'Только главный администратор может блокировать главного администратора.',
                statusCode: 403,
            );
        }
    }
}
