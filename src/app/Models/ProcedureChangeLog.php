<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * История изменений документации процедуры.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property int $changed_by Кто внёс изменение
 * @property string $change_summary Краткое описание изменений
 * @property array|null $diff Детали изменений (diff)
 * @property ApprovalStatus $approval_status Статус согласования изменений
 * @property int|null $approved_by Кто согласовал
 * @property \Illuminate\Support\Carbon|null $approved_at Дата согласования
 * @property \Illuminate\Support\Carbon|null $deadline_extended_to Новый срок после изменений
 * @property \Illuminate\Support\Carbon|null $notifications_sent_at Когда отправлены уведомления
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcedureChangeLog extends Model
{
    protected $fillable = [
        'procedure_id',
        'changed_by',
        'change_summary',
        'diff',
        'approval_status',
        'approved_by',
        'approved_at',
        'deadline_extended_to',
        'notifications_sent_at',
    ];

    /**
     * Преобразование атрибутов записи изменений процедуры в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diff' => 'array',
            'approval_status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
            'deadline_extended_to' => 'datetime',
            'notifications_sent_at' => 'datetime',
        ];
    }

    /**
     * Процедура, документация которой была изменена.
     *
     * Нужен для отображения истории изменений в карточке процедуры и уведомления участников.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Администратор, внёсший изменения в документацию процедуры.
     *
     * Используется для аудита правок и отображения автора изменений.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Администратор, согласовавший изменения документации.
     *
     * Нужен для контроля workflow согласования и фиксации ответственного за утверждение.
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
