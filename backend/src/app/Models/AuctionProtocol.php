<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сгенерированный PDF-протокол аукциона.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура-аукцион
 * @property string $file_path Путь к PDF-протоколу
 * @property int $generated_by Кто сформировал
 * @property \Illuminate\Support\Carbon $generated_at Дата формирования
 * @property int $template_version Версия шаблона протокола
 */
class AuctionProtocol extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_id',
        'file_path',
        'generated_by',
        'generated_at',
        'template_version',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'template_version' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
