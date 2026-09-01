<?php

namespace App\Services;

use App\Models\Procedure;
use App\Models\Setting;

/**
 * Глобальные настройки ЭТП из таблицы settings (фаза 6.7).
 */
class SettingsService
{
    public const DOC_EDIT_DEADLINE_DAYS = 'doc_edit_deadline_days';

    public const RFP_EXTENSION_DAYS = 'rfp_extension_days';

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
}
