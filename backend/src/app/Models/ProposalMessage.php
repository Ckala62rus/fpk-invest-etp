<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сообщение переписки по уточнению заявки.
 *
 * @property int $id Идентификатор
 * @property int $proposal_id Заявка
 * @property int $sender_id Отправитель
 * @property string $message Текст сообщения
 * @property array|null $attachments Вложения
 * @property \Illuminate\Support\Carbon|null $created_at Дата отправки
 */
class ProposalMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'proposal_id',
        'sender_id',
        'message',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
