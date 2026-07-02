<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Группа компаний холдинга (1-й уровень классификатора).
 *
 * @property int $id Идентификатор
 * @property string $name Название группы компаний
 * @property int $sort_order Порядок сортировки
 * @property bool $is_active Активна ли группа
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 */
class CompanyGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function classifierCategories(): HasMany
    {
        return $this->hasMany(ClassifierCategory::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_group_subscriptions');
    }
}
