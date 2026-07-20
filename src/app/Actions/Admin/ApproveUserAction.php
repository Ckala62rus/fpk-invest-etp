<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\User;

/**
 * Действие одобрения зарегистрированного участника ЭТП (электронной торговой площадки).
 */
class ApproveUserAction
{
    /**
     * Активирует пользователя после проверки администратором.
     *
     * @param User $user Пользователь, ожидающий одобрения
     * @param User $approvedBy Администратор, выполнивший одобрение
     * @return User Активированная учётная запись
     */
    public function execute(User $user, User $approvedBy): User
    {
        $user->update([
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
            'status' => UserStatus::Active,
        ]);

        return $user->refresh();
    }
}
