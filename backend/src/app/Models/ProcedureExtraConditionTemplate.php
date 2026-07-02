<?php

namespace App\Models;

use App\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Справочник дополнительных условий аукциона.
 *
 * @property int $id Идентификатор
 * @property string $name Название условия (отсрочка, доставка и т.д.)
 * @property CustomFieldType $field_type Тип значения условия
 * @property bool $is_active Активен ли шаблон
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureExtraConditionTemplate extends Model
{
    protected $fillable = [
        'name',
        'field_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => CustomFieldType::class,
            'is_active' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProcedureExtraConditionValue::class, 'template_id');
    }
}
