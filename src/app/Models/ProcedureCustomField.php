<?php

namespace App\Models;

use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Настраиваемое поле процедуры.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property CustomFieldScope $scope Область применения поля
 * @property string $label Подпись поля
 * @property CustomFieldType $field_type Тип данных поля
 * @property array|null $options Варианты для select
 * @property bool $is_required Обязательное поле
 * @property int $sort_order Порядок отображения
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureCustomField extends Model
{
    protected $fillable = [
        'procedure_id',
        'scope',
        'label',
        'field_type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'scope' => CustomFieldScope::class,
            'field_type' => CustomFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function proposalFieldValues(): HasMany
    {
        return $this->hasMany(ProposalFieldValue::class);
    }
}
