<?php

namespace App\Models;

use App\Enums\AdmissionDecision as AdmissionDecisionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Решение о допуске или недопуске участника.
 *
 * @property int $id Идентификатор
 * @property int $proposal_id Заявка
 * @property AdmissionDecisionEnum $decision Решение: допуск или недопуск
 * @property string|null $reason Причина допуска/недопуска
 * @property int $decided_by Кто принял решение
 * @property \Illuminate\Support\Carbon $decided_at Дата решения
 * @property \Illuminate\Support\Carbon|null $clarification_deadline Срок для уточнения КП
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AdmissionDecision extends Model
{
    protected $fillable = [
        'proposal_id',
        'decision',
        'reason',
        'decided_by',
        'decided_at',
        'clarification_deadline',
    ];

    protected function casts(): array
    {
        return [
            'decision' => AdmissionDecisionEnum::class,
            'decided_at' => 'datetime',
            'clarification_deadline' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
