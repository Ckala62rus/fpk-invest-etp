<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\DTOs\LoginDTO;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Сервис аутентификации пользователя ЭТП (электронной торговой площадки).
 */
class AuthService implements AuthServiceInterface
{
    /**
     * Аутентифицирует пользователя по ИНН и паролю.
     *
     * @param LoginDTO $dto Данные входа (ИНН и пароль)
     * @return User Пользователь с активной учётной записью
     *
     * @throws AuthenticationException Если ИНН или пароль неверны
     * @throws DomainException Если учётная запись ещё не активирована или заблокирована
     */
    public function login(LoginDTO $dto): User
    {
        $user = User::query()
            ->where('inn', $dto->inn)
            ->first();

        $passwordToCheck = $dto->password ?? '';
        $passwordOk = $user !== null && Hash::check($passwordToCheck, $user->password);

        if (!$passwordOk) {
            throw new AuthenticationException('Неверный ИНН или пароль');
        }

        if ($user->status !== UserStatus::Active) {
            throw new DomainException(
                message: $this->inactiveAccountMessage($user->status),
                statusCode: 403,
            );
        }

        Auth::login($user);
        $user->update(['failed_login_attempts' => 0]);

        return $user;
    }

    /**
     * Текст отказа во входе по статусу учётной записи (для UI участника).
     *
     * @param UserStatus $status Текущий статус
     * @return string Сообщение на русском
     */
    private function inactiveAccountMessage(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::PendingEmail => 'Сначала подтвердите email по ссылке из письма. После этого администратор одобрит доступ.',
            UserStatus::PendingApproval => 'Учётные данные верны. Доступ появится после одобрения администратором площадки.',
            UserStatus::Blocked => 'Учётная запись заблокирована. Обратитесь к администратору ЭТП.',
            default => 'Вход доступен только для активной учётной записи.',
        };
    }
}
