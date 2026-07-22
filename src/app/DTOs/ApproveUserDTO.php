<?php

namespace App\DTOs;

use App\Models\User;

/**
 * DTO (Data Transfer Object) одобрения регистрации участника ЭТП (электронной торговой площадки).
 *
 * Связывает пользователя, ожидающего модерации, с администратором, который его активирует.
 */
readonly class ApproveUserDTO
{
    /**
     * @param User $user Пользователь в статусе pending_approval (или ином, допускающем активацию)
     * @param User $approvedBy Администратор (super_admin / trade_admin), выполняющий одобрение
     */
    public function __construct(
        public User $user,
        public User $approvedBy,
    ) {}

    /**
     * Собирает DTO из моделей маршрута и аутентифицированного администратора.
     *
     * @param User $user Пользователь для активации
     * @param User $approvedBy Администратор из текущей сессии
     * @return self
     */
    public static function fromUsers(User $user, User $approvedBy): self
    {
        return new self(
            user: $user,
            approvedBy: $approvedBy,
        );
    }
}
