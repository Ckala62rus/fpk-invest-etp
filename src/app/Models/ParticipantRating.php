<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Агрегированная оценка победителя в профиле участника.
 *
 * @property int $id Идентификатор оценки
 * @property int $winner_user_id Победитель (участник)
 * @property int $procedure_id Процедура
 * @property int|null $contractor_score Оценка подрядчика (1–5)
 * @property int|null $product_score Оценка продукции (1–5)
 * @property string|null $comment Комментарий заказчика
 * @property \Illuminate\Support\Carbon|null $created_at Дата оценки
 */
class ParticipantRating extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'winner_user_id',
        'procedure_id',
        'contractor_score',
        'product_score',
        'comment',
    ];

    /**
     * Преобразование атрибутов оценки участника в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contractor_score' => 'integer',
            'product_score' => 'integer',
        ];
    }

    /**
     * Победитель процедуры, получивший оценку от заказчика.
     *
     * Нужен для отображения рейтинга участника в его профиле и истории выступлений.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    /**
     * Процедура, по итогам которой выставлена оценка.
     *
     * Используется для связи оценки с конкретной закупкой и отображения в карточке процедуры.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
