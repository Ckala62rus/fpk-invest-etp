<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Начальные роли и права RBAC (role-based access control) ЭТП (электронной торговой площадки).
 *
 * Идемпотентный seeder: сначала роли, затем PermissionsSeeder.
 * Тестового супер-админа сюда не создаём — для этого есть SuperAdminSeeder.
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
     * Создаёт системные роли и вызывает seeder прав.
     *
     * @return void
     */
    public function run(): void
    {
        foreach (self::ROLES as $name => $displayName) {
            Role::query()->firstOrCreate(
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

        $this->call(PermissionsSeeder::class);
    }
}
