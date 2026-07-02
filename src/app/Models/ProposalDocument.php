<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Документ, прикреплённый к заявке.
 *
 * @property int $id Идентификатор
 * @property int $proposal_id Заявка
 * @property string $file_path Путь к файлу
 * @property string $file_name Имя файла
 * @property string|null $type Тип документа
 * @property \Illuminate\Support\Carbon|null $created_at Дата загрузки
 */
class ProposalDocument extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'proposal_id',
        'file_path',
        'file_name',
        'type',
    ];

    /**
     * Преобразование атрибутов документа заявки в типы PHP.
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
     * Заявка, к которой прикреплён документ.
     *
     * Нужен для скачивания файлов КП и сопутствующих документов при рассмотрении заявки.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
