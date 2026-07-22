<?php

namespace App\Actions\Admin;

use App\DTOs\ApproveUserDTO;
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
     * @param ApproveUserDTO $dto Пользователь и администратор, выполнивший одобрение
     * @return User Активированная учётная запись
     */
    public function execute(ApproveUserDTO $dto): User
    {
        $dto->user->update([
            'approved_at' => now(),
            'approved_by' => $dto->approvedBy->id,
            'status' => UserStatus::Active,
        ]);

        return $dto->user->refresh();
    }
}
