<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Значение дополнительного условия для конкретной процедуры.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property int $template_id Шаблон условия
 * @property string|null $value Значение условия
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureExtraConditionValue extends Model
{
    protected $fillable = [
        'procedure_id',
        'template_id',
        'value',
    ];

    /**
     * Процедура, для которой задано значение дополнительного условия.
     *
     * Нужен для отображения условий аукциона (отсрочка, доставка и т.д.) на странице процедуры.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Шаблон дополнительного условия из справочника.
     *
     * Используется для получения названия и типа поля условия при формировании карточки аукциона.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureExtraConditionTemplate::class, 'template_id');
    }
}
