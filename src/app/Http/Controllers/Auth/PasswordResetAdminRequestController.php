<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Models\PasswordResetAdminRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер обращений к администратору для восстановления доступа к ЭТП.
 */
class PasswordResetAdminRequestController extends ApiController
{
    /**
     * Создаёт обращение по ИНН (идентификационному номеру налогоплательщика).
     *
     * @param Request $request HTTP-запрос с ИНН и текстом обращения
     * @return JsonResponse Единый JSON-ответ о созданном обращении
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'inn' => ['required', 'string', 'max:12'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);
        $user = User::query()->where('inn', $data['inn'])->first();

        $adminRequest = PasswordResetAdminRequest::query()->create([
            'user_id' => $user?->id,
            'inn' => $data['inn'],
            'message' => $data['message'] ?? null,
        ]);

        return $this->created(['id' => $adminRequest->id], 'Запрос передан администратору.');
    }
}
