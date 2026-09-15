<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreSiteLogoRequest;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Управление логотипом площадки (super_admin).
 */
class SiteLogoController extends ApiController
{
    /**
     * @param SettingsService $settings Настройки
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Загружает логотип для публичной шапки.
     *
     * @param StoreSiteLogoRequest $request Файл logo
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function store(StoreSiteLogoRequest $request): JsonResponse
    {
        $this->assertSuperAdmin();

        /** @var User $user */
        $user = $request->user();

        $this->settings->storeSiteLogo($request->file('logo'), $user->id);

        return $this->success([
            'logo_url' => '/api/site-logo/file',
            'has_custom_logo' => true,
        ], 'Логотип обновлён.');
    }

    /**
     * Удаляет логотип — на витрине снова дефолт «ФИ».
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function destroy(): JsonResponse
    {
        $this->assertSuperAdmin();

        /** @var User|null $user */
        $user = request()->user();

        $this->settings->clearSiteLogo($user?->id);

        return $this->success([
            'logo_url' => null,
            'has_custom_logo' => false,
        ], 'Логотип удалён, используется оформление по умолчанию.');
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
            throw new AccessDeniedHttpException('Логотип может менять только super_admin.');
        }
    }
}
