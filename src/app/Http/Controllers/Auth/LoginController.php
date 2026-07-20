<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\AuthServiceInterface;
use App\DTOs\LoginDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\LoginRequest;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер входа пользователя в ЭТП (электронную торговую площадку).
 *
 * Передаёт проверенные данные сервису, который выполняет аутентификацию сессии Sanctum.
 */
class LoginController extends ApiController
{
    /**
     * Сервис проверки учётных данных и создания сессии.
     *
     * @var AuthServiceInterface
     */
    private readonly AuthServiceInterface $authService;

    /**
     * Создаёт контроллер входа.
     *
     * @param AuthServiceInterface $authService Сервис аутентификации пользователя
     * @return void
     */
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Выполняет вход по ИНН (идентификационному номеру налогоплательщика) и паролю.
     *
     * @param LoginRequest $request Проверенный HTTP-запрос входа
     * @return JsonResponse Единый JSON-ответ с идентификатором пользователя
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(LoginDTO::fromArray($request->validated()));

        $request->session()->regenerate();

        return $this->success(['user_id' => $user->id], 'Вход выполнен');
    }
}
