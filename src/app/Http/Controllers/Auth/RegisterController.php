<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\DTOs\RegisterUserDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\RegistrationRequest;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер публичной регистрации участника ЭТП (электронной торговой площадки).
 */
class RegisterController extends ApiController
{
    /**
     * Действие создания учётной записи участника.
     *
     * @var RegisterUserAction
     */
    private readonly RegisterUserAction $registerUser;

    /**
     * Создаёт контроллер регистрации.
     *
     * @param RegisterUserAction $registerUser Действие регистрации участника
     * @return void
     */
    public function __construct(RegisterUserAction $registerUser)
    {
        $this->registerUser = $registerUser;
    }

    /**
     * Регистрирует участника и запускает подтверждение email.
     *
     * @param RegistrationRequest $request Проверенный HTTP-запрос регистрации
     * @return JsonResponse Единый JSON-ответ о созданной учётной записи
     */
    public function store(RegistrationRequest $request): JsonResponse
    {
        $user = $this->registerUser->execute(
            RegisterUserDTO::fromArray($request->validated()),
        );

        return $this->created(
            ['user_id' => $user->id, 'status' => $user->status->value],
            'Регистрация завершена. Подтвердите email.',
        );
    }
}
