<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Учредительный или регистрационный документ пользователя.
 *
 * @property int $id Идентификатор
 * @property int $user_id Пользователь
 * @property string $file_path Путь к файлу
 * @property string $file_name Имя файла
 * @property string|null $mime_type MIME-тип файла
 * @property int|null $size Размер файла в байтах
 * @property \Illuminate\Support\Carbon $valid_until Срок действия документа
 * @property \Illuminate\Support\Carbon|null $uploaded_at Дата загрузки
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserDocument extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'valid_until',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'uploaded_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
