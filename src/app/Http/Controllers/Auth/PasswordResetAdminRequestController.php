<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\AdminPasswordResetRequest;
use App\Models\PasswordResetAdminRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер обращений к администратору для восстановления доступа к ЭТП.
 */
class PasswordResetAdminRequestController extends ApiController
{
    /**
     * Создаёт обращение по ИНН (идентификационному номеру налогоплательщика).
     *
     * @param AdminPasswordResetRequest $request Проверенный запрос с ИНН и текстом
     * @return JsonResponse Единый JSON-ответ о созданном обращении
     */
    public function store(AdminPasswordResetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::query()->where('inn', $data['inn'])->first();

        $adminRequest = PasswordResetAdminRequest::query()->create([
            'user_id' => $user?->id,
            'inn' => $data['inn'],
            'message' => $data['message'] ?? null,
        ]);

        return $this->created(['id' => $adminRequest->id], 'Запрос передан администратору.');
    }
}
