<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\ApiController;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Публичный логотип ЭТП для шапки витрины.
 *
 * Пути /api/site-logo* (не /branding) — adblock часто блокирует «branding» в URL.
 */
class SiteLogoPublicController extends ApiController
{
    /**
     * @param SettingsService $settings Настройки площадки
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Метаданные логотипа (URL или null → дефолт «ФИ» на фронте).
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $path = $this->settings->siteLogoPath();
        $disk = Storage::disk('local');
        $hasCustomLogo = $path !== null && $disk->exists($path);

        return $this->success([
            'logo_url' => $hasCustomLogo
                ? '/api/site-logo/file?v='.$disk->lastModified($path)
                : null,
            'has_custom_logo' => $hasCustomLogo,
        ], 'Логотип площадки.');
    }

    /**
     * Отдаёт файл логотипа (если загружен).
     *
     * @return StreamedResponse
     *
     * @throws NotFoundHttpException
     */
    public function file(): StreamedResponse
    {
        $path = $this->settings->siteLogoPath();

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            throw new NotFoundHttpException('Логотип не загружен.');
        }

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';

        return Storage::disk('local')->response($path, 'logo', [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
