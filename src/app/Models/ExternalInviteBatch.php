<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Массовое приглашение незарегистрированных email на процедуру.
 *
 * @property int $id Идентификатор рассылки
 * @property int $procedure_id Процедура
 * @property array $emails Список email для приглашения
 * @property array|null $duplicates_skipped Дубликаты, не отправленные
 * @property int $created_by Кто инициировал рассылку
 * @property \Illuminate\Support\Carbon|null $sent_at Дата отправки
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class ExternalInviteBatch extends Model
{
    protected $fillable = [
        'procedure_id',
        'emails',
        'duplicates_skipped',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'emails' => 'array',
            'duplicates_skipped' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
