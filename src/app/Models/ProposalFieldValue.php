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

    /**
     * Заявка, в которой заполнено это значение поля.
     *
     * Нужен для получения всех ответов участника при просмотре и рассмотрении заявки.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Настраиваемое поле процедуры, значение которого хранится в записи.
     *
     * Используется для получения метаданных поля (подпись, тип, обязательность) при отображении ответа.
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(ProcedureCustomField::class, 'procedure_custom_field_id');
    }
}
