<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Роль пользователя ЭТП.
 *
 * @property int $id Идентификатор
 * @property string $name Системное имя роли
 * @property string $display_name Отображаемое название роли
 * @property bool $is_system Системная роль (нельзя удалить)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }
}
