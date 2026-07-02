<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Предприятие-заказчик холдинга.
 *
 * @property int $id Идентификатор
 * @property int $company_group_id Группа компаний
 * @property string $name Наименование предприятия
 * @property string|null $inn ИНН предприятия
 * @property bool $is_external Внешний заказчик вне холдинга
 * @property bool $is_active Активно ли предприятие
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 */
class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_group_id',
        'name',
        'inn',
        'is_external',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class);
    }
}
