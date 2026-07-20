<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер управления собственным профилем пользователя ЭТП (электронной торговой площадки).
 */
class ProfileController extends ApiController
{
    /**
     * Возвращает профиль текущего пользователя.
     *
     * @param Request $request Аутентифицированный HTTP-запрос
     * @return JsonResponse Единый JSON-ответ с профилем
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success(new UserProfileResource($request->user()->load('profile')->profile));
    }

    /**
     * Обновляет только профиль текущего пользователя.
     *
     * @param Request $request Аутентифицированный HTTP-запрос с данными профиля
     * @return JsonResponse Единый JSON-ответ с обновлённым профилем
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['sometimes', 'in:legal,individual'],
            'name' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'director_name' => ['sometimes', 'string', 'max:255'],
            'director_birth_date' => ['nullable', 'date'],
            'contact_persons' => ['sometimes', 'string'],
        ]);

        $profile = $request->user()->profile;
        $profile->update($data);

        return $this->success(new UserProfileResource($profile->refresh()), 'Профиль обновлён.');
    }
}
