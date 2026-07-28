<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\UpdateNotificationSettingsRequest;
use App\Http\Resources\UserNotificationSettingResource;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Настройки email-оповещений участника ЭТП (электронной торговой площадки).
 *
 * Фаза 3.5: запись создаётся при регистрации; участник читает/обновляет только свои настройки.
 */
class UserNotificationSettingController extends ApiController
{
    /**
     * Возвращает настройки оповещений текущего пользователя.
     *
     * @param Request $request Запрос с сессией Sanctum
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $settings = $this->resolveSettings($request);

        return $this->success(
            new UserNotificationSettingResource($settings),
            'Настройки оповещений.',
        );
    }

    /**
     * Обновляет настройки оповещений текущего пользователя.
     *
     * @param UpdateNotificationSettingsRequest $request Валидированные флаги
     * @return JsonResponse
     */
    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $settings = $this->resolveSettings($request);
        $settings->update($request->validated());

        return $this->success(
            new UserNotificationSettingResource($settings->refresh()),
            'Настройки оповещений обновлены.',
        );
    }

    /**
     * Возвращает существующие настройки или создаёт дефолтные (идемпотентно).
     *
     * @param Request $request Запрос с пользователем
     * @return UserNotificationSetting
     */
    private function resolveSettings(Request $request): UserNotificationSetting
    {
        $user = $request->user();

        return UserNotificationSetting::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'all_disabled' => false,
                'notify_new_auctions' => true,
                'notify_new_procedures' => true,
                'notify_day_before' => true,
                'notify_hour_before' => true,
            ],
        );
    }
}
