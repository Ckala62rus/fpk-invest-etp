<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Дефолтные глобальные настройки ЭТП (фаза 6.7).
 */
class SettingsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $defaults = [
            SettingsService::DOC_EDIT_DEADLINE_DAYS => 2,
            SettingsService::RFP_EXTENSION_DAYS => 5,
        ];

        foreach ($defaults as $key => $days) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => ['days' => $days]],
            );
        }
    }
}
