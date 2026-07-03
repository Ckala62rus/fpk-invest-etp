<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Начальные роли RBAC ЭТП (идемпотентный seeder).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Системные роли: slug => отображаемое название.
     *
     * @var array<string, string>
     */
    private const ROLES = [
        'super_admin' => 'Главный администратор',
        'trade_admin' => 'Администратор торгов',
        'auditor' => 'Аудитор',
        'participant' => 'Участник',
        'guest' => 'Гость',
    ];

    /**
     * Создаёт роли, если они ещё не существуют.
     */
    public function run(): void
    {
        foreach (self::ROLES as $name => $displayName) {
            Role::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $displayName,
                    'is_system' => true,
                ],
            );
        }
    }
}
