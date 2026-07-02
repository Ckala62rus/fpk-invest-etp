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

    /**
     * Преобразование атрибутов участника процедуры в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ParticipantStatus::class,
            'admitted_at' => 'datetime',
        ];
    }

    /**
     * Процедура, в которой участвует пользователь.
     *
     * Нужен для отображения списка участников процедуры и проверки допуска к торгам.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Участник процедуры (зарегистрированный пользователь).
     *
     * Используется для отображения данных участника и связи с его заявками и ставками.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Администратор, допустивший участника к процедуре.
     *
     * Нужен для аудита решений о допуске и отображения ответственного лица.
     */
    public function admittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }
}
