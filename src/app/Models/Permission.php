<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Право доступа в системе RBAC.
 *
 * Расширяет Spatie Permission. Связь `roles()` предоставляется пакетом
 * (таблица role_has_permissions) — роли, которым назначено это право.
 *
 * @property int $id Идентификатор
 * @property string $name Системное имя права
 * @property string $guard_name Guard аутентификации (web)
 * @property string|null $group Группа для отображения в UI
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Permission extends SpatiePermission
{
}
