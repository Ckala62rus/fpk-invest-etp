<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Mail\PasswordResetMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Контроллер самостоятельного восстановления пароля на ЭТП (электронной торговой площадке).
 */
class PasswordResetController extends ApiController
{
    /**
     * Выпускает токен восстановления и отправляет его владельцу email.
     *
     * @param Request $request HTTP-запрос с email пользователя
     * @return JsonResponse Единый JSON-ответ без раскрытия существования email
     */
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->where('email', $data['email'])->first();

        if ($user !== null) {
            /** @var PasswordResetToken $token */
            $token = DB::transaction(function () use ($user): PasswordResetToken {
                PasswordResetToken::query()
                    ->where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);

                return PasswordResetToken::query()->create([
                    'user_id' => $user->id,
                    'token' => Str::random(64),
                    'expires_at' => now()->addMinutes(60),
                ]);
            });

            Mail::to($user->email)->send(new PasswordResetMail($token));
        }

        return $this->success(null, 'Если email зарегистрирован, инструкция отправлена.');
    }

    /**
     * Сбрасывает пароль по действующему одноразовому токену.
     *
     * @param Request $request HTTP-запрос с токеном и новым паролем
     * @return JsonResponse Единый JSON-ответ о смене пароля
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $token = PasswordResetToken::query()
            ->where('token', $data['token'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($token === null) {
            return $this->error('Токен восстановления недействителен или истёк.', 422);
        }

        DB::transaction(function () use ($token, $data): void {
            $token->user->update(['password' => $data['password']]);
            $token->update(['used_at' => now()]);
        });

        return $this->success(null, 'Пароль изменён.');
    }
}
