<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Лог обращений к заявке (просмотр, скачивание).
 *
 * @property int $id Идентификатор
 * @property int $proposal_id Заявка
 * @property int $user_id Пользователь
 * @property string $action Действие: view, download и т.д.
 * @property string|null $ip_address IP-адрес
 * @property \Illuminate\Support\Carbon|null $created_at Дата действия
 */
class ProposalAccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'proposal_id',
        'user_id',
        'action',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
