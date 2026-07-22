<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder прав RBAC (role-based access control) ЭТП (электронной торговой площадки).
 *
 * Создаёт permissions по модулям и синхронизирует их с системными ролями.
 * Идемпотентен: повторный запуск обновляет привязки через syncPermissions.
 */
class PermissionsSeeder extends Seeder
{
    /**
     * Каталог прав: системное имя => группа для UI.
     *
     * Описание права хранится в комментарии рядом с ключом (колонки description в БД нет).
     *
     * @var array<string, array{group: string}>
     */
    private const PERMISSIONS = [
        // Пользователи
        'users.view' => ['group' => 'users'], // Просмотр списка пользователей
        'users.approve' => ['group' => 'users'], // Подтверждение регистрации
        'users.block' => ['group' => 'users'], // Блокировка / разблокировка
        'users.assign_roles' => ['group' => 'users'], // Назначение ролей (только super_admin)
        'users.delete' => ['group' => 'users'], // Soft delete пользователя

        // Аудит
        'activity_log.view' => ['group' => 'audit'], // Просмотр журнала действий
        'activity_log.export' => ['group' => 'audit'], // Экспорт журнала

        // ТЗП (торгово-закупочные процедуры) — заготовки под следующие фазы
        'procedures.view' => ['group' => 'procedures'],
        'procedures.create' => ['group' => 'procedures'],
        'procedures.update' => ['group' => 'procedures'],
        'procedures.delete' => ['group' => 'procedures'],
        'procedures.publish' => ['group' => 'procedures'],
        'procedures.approve' => ['group' => 'procedures'], // Согласование изменений аудитором

        // Лоты
        'lots.view' => ['group' => 'lots'],
        'lots.create' => ['group' => 'lots'],
        'lots.update' => ['group' => 'lots'],
        'lots.delete' => ['group' => 'lots'],

        // Ставки аукциона
        'bids.view' => ['group' => 'bids'],
        'bids.create' => ['group' => 'bids'],
        'bids.cancel' => ['group' => 'bids'], // Отмена ставки администратором

        // Глобальные настройки
        'settings.view' => ['group' => 'admin'],
        'settings.update' => ['group' => 'admin'],
    ];

    /**
     * Права по ролям. Для super_admin используется маркер '*' (все права).
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'super_admin' => ['*'],
        'trade_admin' => [
            'users.view',
            'users.approve',
            'users.block',
            // без users.assign_roles и users.delete
            'activity_log.view',
            'procedures.view',
            'procedures.create',
            'procedures.update',
            'procedures.publish',
            'lots.view',
            'lots.create',
            'lots.update',
            'bids.view',
            'bids.cancel',
        ],
        'auditor' => [
            'users.view',
            'activity_log.view',
            'activity_log.export',
            'procedures.view',
            'procedures.approve',
            'lots.view',
            'bids.view',
        ],
        'participant' => [
            // Свой профиль идёт через /api/profile без users.view (это админский список)
            'procedures.view',
            'lots.view',
            'bids.view',
            'bids.create',
        ],
        'guest' => [
            'procedures.view',
            'lots.view',
        ],
    ];

    /**
     * Создаёт права и назначает их ролям.
     *
     * @return void
     */
    public function run(): void
    {
        $this->createPermissions();
        $this->assignPermissionsToRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Создаёт отсутствующие permissions (без дублей по name + guard).
     *
     * @return void
     */
    private function createPermissions(): void
    {
        foreach (self::PERMISSIONS as $name => $config) {
            Permission::query()->firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'group' => $config['group'],
                ],
            );
        }
    }

    /**
     * Синхронизирует набор прав для каждой системной роли.
     *
     * @return void
     */
    private function assignPermissionsToRoles(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role === null) {
                continue;
            }

            if (in_array('*', $permissionNames, true)) {
                $permissionNames = array_keys(self::PERMISSIONS);
            }

            $role->syncPermissions($permissionNames);
        }
    }
}
