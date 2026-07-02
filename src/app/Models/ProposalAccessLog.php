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

    /**
     * Преобразование атрибутов записи доступа к заявке в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Заявка, к которой был выполнен доступ (просмотр или скачивание).
     *
     * Нужен для аудита обращений к конфиденциальным данным заявки.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Пользователь, выполнивший действие с заявкой.
     *
     * Используется для фиксации, кто и когда просматривал или скачивал материалы заявки.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
