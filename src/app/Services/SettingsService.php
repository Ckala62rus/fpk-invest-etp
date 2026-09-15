<?php

namespace App\Services;

use App\Models\Procedure;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Глобальные настройки ЭТП из таблицы settings (фаза 6.7).
 */
class SettingsService
{
    public const DOC_EDIT_DEADLINE_DAYS = 'doc_edit_deadline_days';

    public const RFP_EXTENSION_DAYS = 'rfp_extension_days';

    public const PROPOSAL_RETENTION_YEARS = 'proposal_retention_years';

    /** Ключ пути к загруженному логотипу площадки (storage/app/...). */
    public const SITE_LOGO = 'site_logo';

    /**
     * Читает целочисленную настройку.
     *
     * @param string $key Ключ настройки
     * @param int $default Значение по умолчанию
     * @return int
     */
    public function getInt(string $key, int $default): int
    {
        $setting = Setting::query()->find($key);

        if ($setting === null) {
            return $default;
        }

        $value = $setting->value;

        if (is_array($value) && array_key_exists('days', $value)) {
            return (int) $value['days'];
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * Можно ли редактировать документацию процедуры (не позднее N дней до ends_at).
     *
     * @param Procedure $procedure ТЗП с ends_at
     * @return bool
     */
    public function canEditProcedureDocuments(Procedure $procedure): bool
    {
        if ($procedure->ends_at === null) {
            return false;
        }

        $days = $this->getInt(self::DOC_EDIT_DEADLINE_DAYS, 2);
        $cutoff = $procedure->ends_at->copy()->subDays($days);

        return now()->lessThanOrEqualTo($cutoff);
    }

    /**
     * Количество дней продления приёма КП после согласования изменений документации.
     *
     * @return int
     */
    public function rfpExtensionDays(): int
    {
        return $this->getInt(self::RFP_EXTENSION_DAYS, 5);
    }

    /**
     * Сохраняет целочисленную настройку (дни).
     *
     * @param string $key Ключ
     * @param int $days Значение
     * @param int|null $updatedBy ID пользователя
     * @return void
     */
    public function setDays(string $key, int $days, ?int $updatedBy = null): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => ['days' => $days],
                'updated_by' => $updatedBy,
            ],
        );
    }

    /**
     * Относительный путь к логотипу в диске local или null (дефолт «ФИ» на фронте).
     *
     * @return string|null
     */
    public function siteLogoPath(): ?string
    {
        $setting = Setting::query()->find(self::SITE_LOGO);
        $path = is_array($setting?->value) ? ($setting->value['path'] ?? null) : null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Сохраняет новый логотип и заменяет настройку до удаления предыдущего файла.
     *
     * Если запись настройки не удалась, рабочий логотип сохраняется, а новый файл удаляется.
     *
     * @param UploadedFile $file Изображение
     * @param int|null $updatedBy ID администратора
     * @return string Путь в storage
     *
     * @throws \Throwable Если не удалось сохранить настройку логотипа
     */
    public function storeSiteLogo(UploadedFile $file, ?int $updatedBy = null): string
    {
        $previousPath = $this->siteLogoPath();
        $path = $file->store('branding', 'local');

        try {
            Setting::query()->updateOrCreate(
                ['key' => self::SITE_LOGO],
                [
                    'value' => [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ],
                    'updated_by' => $updatedBy,
                ],
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($previousPath !== null && $previousPath !== $path && Storage::disk('local')->exists($previousPath)) {
            Storage::disk('local')->delete($previousPath);
        }

        return $path;
    }

    /**
     * Удаляет логотип: снова показывается дефолтный значок «ФИ».
     *
     * @param int|null $updatedBy ID администратора
     * @return void
     */
    public function clearSiteLogo(?int $updatedBy = null): void
    {
        $path = $this->siteLogoPath();

        Setting::query()->updateOrCreate(
            ['key' => self::SITE_LOGO],
            [
                'value' => ['path' => null],
                'updated_by' => $updatedBy,
            ],
        );

        if ($path !== null && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Удаляет файл логотипа с диска, если он есть.
     *
     * @return void
     */
    private function deleteSiteLogoFile(): void
    {
        $path = $this->siteLogoPath();

        if ($path !== null && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
