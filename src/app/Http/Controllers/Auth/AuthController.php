<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Контроллер управления текущей сессией Sanctum SPA (Single Page Application).
 */
class AuthController extends ApiController
{
    /**
     * Завершает текущую браузерную сессию пользователя.
     *
     * @param Request $request Аутентифицированный HTTP-запрос
     * @return JsonResponse Единый JSON-ответ о выходе
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Выход выполнен');
    }

    /**
     * Возвращает текущего пользователя с ролями и профилем.
     *
     * @param Request $request Аутентифицированный HTTP-запрос
     * @return JsonResponse Единый JSON-ответ с данными пользователя
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'profile']);

        return $this->success(new UserResource($user));
    }
}
