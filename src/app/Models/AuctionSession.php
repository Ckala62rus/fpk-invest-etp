<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сессия посещения страницы аукциона.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура-аукцион
 * @property int|null $user_id Участник (null для гостя)
 * @property \Illuminate\Support\Carbon $first_seen_at Первый визит
 * @property \Illuminate\Support\Carbon $last_seen_at Последняя активность
 * @property bool $is_online Сейчас на странице аукциона
 */
class AuctionSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_id',
        'user_id',
        'first_seen_at',
        'last_seen_at',
        'is_online',
    ];

    /**
     * Преобразование атрибутов сессии посещения аукциона в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    /**
     * Процедура-аукцион, страницу которой посещает участник.
     *
     * Нужен для подсчёта онлайн-участников и отображения счётчика зрителей на странице аукциона.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Участник, посетивший страницу аукциона (null для анонимного гостя).
     *
     * Используется для учёта активности участников и отображения списка присутствующих администратору.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
