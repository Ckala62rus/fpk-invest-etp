<?php

namespace App\Contracts;

use App\DTOs\LoginDTO;
use App\Models\User;

/**
 * Контракт сервиса аутентификации пользователей ЭТП (электронной торговой площадки).
 */
interface AuthServiceInterface
{
    /**
     * Аутентифицирует пользователя по ИНН и паролю.
     *
     * @param LoginDTO $dto Данные входа (ИНН и пароль)
     * @return User Пользователь с активной учётной записью
     */
    public function login(LoginDTO $dto): User;
}
