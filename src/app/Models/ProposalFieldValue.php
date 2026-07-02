<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Значение настраиваемого поля заявки.
 *
 * @property int $proposal_id Заявка
 * @property int $procedure_custom_field_id Настраиваемое поле
 * @property string|null $value Значение поля
 */
class ProposalFieldValue extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'proposal_id',
        'procedure_custom_field_id',
        'value',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(ProcedureCustomField::class, 'procedure_custom_field_id');
    }
}
