<?php

namespace App\Models;

use App\Enums\ReportFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запуск формирования отчёта.
 *
 * @property int $id Идентификатор запуска отчёта
 * @property int $template_id Шаблон отчёта
 * @property array|null $filters Применённые фильтры
 * @property string|null $file_path Путь к сгенерированному файлу
 * @property ReportFormat $format Формат файла отчёта
 * @property int $generated_by Кто сформировал отчёт
 * @property \Illuminate\Support\Carbon $generated_at Дата формирования
 */
class ReportRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'filters',
        'file_path',
        'format',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'format' => ReportFormat::class,
            'generated_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
