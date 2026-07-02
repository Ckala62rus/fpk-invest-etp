<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Право доступа в системе RBAC.
 *
 * @property int $id Идентификатор
 * @property string $name Системное имя права
 * @property string|null $group Группа для отображения в UI
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'group',
    ];

    /**
     * Роли, которым назначено это право доступа.
     *
     * Нужен для управления матрицей RBAC и проверки разрешений при авторизации действий.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
