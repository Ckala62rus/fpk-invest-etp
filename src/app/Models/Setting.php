<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Глобальная настройка ЭТП (key-value).
 *
 * @property string $key Ключ настройки
 * @property array $value Значение настройки (JSON)
 * @property int|null $updated_by Кто последний изменил
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class Setting extends Model
{
    public $incrementing = false;

    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
