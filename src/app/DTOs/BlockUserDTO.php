<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\BlockUserRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * DTO (Data Transfer Object) блокировки пользователя ЭТП (электронной торговой площадки).
 */
readonly class BlockUserDTO
{
    /**
     * @param User $user Пользователь, которого блокируют
     * @param User $blockedBy Администратор (super_admin / trade_admin)
     * @param string $reason Причина блокировки
     * @param Carbon|null $blockedUntil Дата окончания блокировки; null — до ручной разблокировки
     * @return void
     */
    public function __construct(
        public User $user,
        public User $blockedBy,
        public string $reason,
        public ?Carbon $blockedUntil = null,
    ) {
    }

    /**
     * Собирает DTO из маршрута, сессии и валидированного FormRequest.
     *
     * @param User $user Цель блокировки
     * @param User $blockedBy Администратор из текущей сессии
     * @param BlockUserRequest $request Валидированные reason и blocked_until
     * @return self
     */
    public static function fromRequest(User $user, User $blockedBy, BlockUserRequest $request): self
    {
        $until = $request->validated('blocked_until');

        return new self(
            user: $user,
            blockedBy: $blockedBy,
            reason: (string) $request->validated('reason'),
            blockedUntil: is_string($until) && $until !== ''
                ? Carbon::parse($until)
                : null,
        );
    }
}
