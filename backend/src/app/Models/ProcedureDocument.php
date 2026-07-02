<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Конкурсная документация процедуры.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property string $file_path Путь к файлу
 * @property string $file_name Имя файла
 * @property int $version Версия документации
 * @property int $uploaded_by Кто загрузил
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProcedureDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'procedure_id',
        'file_path',
        'file_name',
        'version',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
