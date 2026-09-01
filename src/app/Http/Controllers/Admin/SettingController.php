<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Глобальные настройки ЭТП (фаза 6.7) — только super_admin.
 */
class SettingController extends ApiController
{
    /**
     * @param SettingsService $settings Сервис настроек
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Ключевые настройки для админки.
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(): JsonResponse
    {
        $this->assertSuperAdmin();

        return $this->success([
            'doc_edit_deadline_days' => $this->settings->getInt(
                SettingsService::DOC_EDIT_DEADLINE_DAYS,
                2,
            ),
            'rfp_extension_days' => $this->settings->rfpExtensionDays(),
        ], 'Настройки площадки.');
    }

    /**
     * Обновляет настройки (doc_edit_deadline_days, rfp_extension_days).
     *
     * @param Request $request Тело запроса
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function update(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $data = $request->validate([
            'doc_edit_deadline_days' => ['sometimes', 'integer', 'min:0', 'max:30'],
            'rfp_extension_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ], [
            'doc_edit_deadline_days.integer' => 'Срок редактирования документов должен быть числом.',
            'doc_edit_deadline_days.min' => 'Срок редактирования не может быть отрицательным.',
            'doc_edit_deadline_days.max' => 'Срок редактирования не должен превышать :max дней.',
            'rfp_extension_days.integer' => 'Срок продления приёма КП должен быть числом.',
            'rfp_extension_days.min' => 'Срок продления должен быть не менее :min дня.',
            'rfp_extension_days.max' => 'Срок продления не должен превышать :max дней.',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (array_key_exists('doc_edit_deadline_days', $data)) {
            $this->settings->setDays(
                SettingsService::DOC_EDIT_DEADLINE_DAYS,
                (int) $data['doc_edit_deadline_days'],
                $user->id,
            );
        }

        if (array_key_exists('rfp_extension_days', $data)) {
            $this->settings->setDays(
                SettingsService::RFP_EXTENSION_DAYS,
                (int) $data['rfp_extension_days'],
                $user->id,
            );
        }

        return $this->success([
            'doc_edit_deadline_days' => $this->settings->getInt(
                SettingsService::DOC_EDIT_DEADLINE_DAYS,
                2,
            ),
            'rfp_extension_days' => $this->settings->rfpExtensionDays(),
        ], 'Настройки обновлены.');
    }

    /**
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertSuperAdmin(): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null || ! $user->hasRole('super_admin')) {
            throw new AccessDeniedHttpException('Настройки площадки доступны только super_admin.');
        }
    }
}
