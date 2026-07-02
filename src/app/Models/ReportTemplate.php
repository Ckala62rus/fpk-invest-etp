<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон отчёта.
 *
 * @property int $id Идентификатор шаблона отчёта
 * @property string $name Название шаблона
 * @property array $query_config Конфигурация выборки данных
 * @property array $columns Колонки отчёта
 * @property int $created_by Кто создал шаблон
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 */
class ReportTemplate extends Model
{
    protected $fillable = [
        'name',
        'query_config',
        'columns',
        'created_by',
    ];

    /**
     * Преобразование атрибутов шаблона отчёта в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query_config' => 'array',
            'columns' => 'array',
        ];
    }

    /**
     * Пользователь, создавший шаблон отчёта.
     *
     * Нужен для аудита создания шаблонов и отображения автора в списке отчётов.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Все запуски формирования отчёта по этому шаблону.
     *
     * Используется для отображения истории генераций и скачивания ранее сформированных файлов.
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'template_id');
    }
}
