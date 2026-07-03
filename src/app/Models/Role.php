<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Роль пользователя ЭТП.
 *
 * Расширяет Spatie Role. Связи `users()` и `permissions()` предоставляются пакетом:
 * users — участники с этой ролью (model_has_roles);
 * permissions — права, входящие в роль (role_has_permissions).
 *
 * @property int $id Идентификатор
 * @property string $name Системное имя роли
 * @property string $guard_name Guard аутентификации (web)
 * @property string $display_name Отображаемое название роли
 * @property bool $is_system Системная роль (нельзя удалить)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Role extends SpatieRole
{
    /**
     * Преобразование атрибутов роли в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
