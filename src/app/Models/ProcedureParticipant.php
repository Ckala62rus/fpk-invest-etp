<?php

namespace App\Models;

use App\Enums\ParticipantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Приглашённый или допущенный участник процедуры.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property int $user_id Участник
 * @property ParticipantStatus $status Статус участника в процедуре
 * @property \Illuminate\Support\Carbon|null $admitted_at Дата допуска
 * @property int|null $admitted_by Кто допустил
 * @property string|null $rejection_reason Причина отклонения
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureParticipant extends Model
{
    protected $fillable = [
        'procedure_id',
        'user_id',
        'status',
        'admitted_at',
        'admitted_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ParticipantStatus::class,
            'admitted_at' => 'datetime',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }
}
