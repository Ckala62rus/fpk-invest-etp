<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Действие разблокировки учётной записи участника ЭТП (электронной торговой площадки).
 *
 * Снимает блокировку, возвращает статус `active`, пишет activity log.
 */
class UnblockUserAction
{
    /**
     * Разблокирует пользователя от имени администратора.
     *
     * @param User $user Заблокированный пользователь
     * @param User $unblockedBy Администратор (super_admin / trade_admin)
     * @return User Разблокированная учётная запись
     *
     * @throws DomainException Если пользователь не заблокирован
     */
    public function execute(User $user, User $unblockedBy): User
    {
        if ($user->status !== UserStatus::Blocked) {
            throw new DomainException(
                message: 'Пользователь не заблокирован.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($user, $unblockedBy): User {
            $user->disableLogging();
            $user->update([
                'status' => UserStatus::Active,
                'block_reason' => null,
                'blocked_until' => null,
            ]);
            $user->enableLogging();

            activity('user')
                ->causedBy($unblockedBy)
                ->performedOn($user)
                ->event('unblocked')
                ->withProperties([])
                ->log('Пользователь разблокирован');

            return $user->refresh()->load(['roles', 'profile']);
        });
    }
}
