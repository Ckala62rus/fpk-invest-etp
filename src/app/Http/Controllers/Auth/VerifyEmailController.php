<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер подтверждения email участника ЭТП (электронной торговой площадки).
 */
class VerifyEmailController extends ApiController
{
    /**
     * Подтверждает email по временной подписанной ссылке.
     *
     * @param Request $request HTTP-запрос со строкой подписи
     * @param User $user Пользователь из параметра маршрута
     * @param string $hash SHA-1-хеш подтверждаемого email
     * @return JsonResponse Единый JSON-ответ о подтверждении
     */
    public function __invoke(Request $request, User $user, string $hash): JsonResponse
    {
        if (!hash_equals(sha1($user->email), $hash)) {
            return $this->error('Недействительная ссылка подтверждения email.', 403);
        }

        if ($user->email_verified_at === null) {
            $user->update([
                'email_verified_at' => now(),
                'status' => UserStatus::PendingApproval,
            ]);
        }

        return $this->success(
            ['user_id' => $user->id, 'status' => $user->status->value],
            'Email подтверждён. Ожидайте одобрения администратора.',
        );
    }
}
