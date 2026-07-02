<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ревизия (версия) страницы CMS.
 *
 * @property int $id Идентификатор ревизии
 * @property int $page_id Страница CMS
 * @property string $content_html HTML-содержимое страницы
 * @property int $revised_by Кто отредактировал
 * @property \Illuminate\Support\Carbon|null $created_at Дата ревизии
 */
class CmsPageRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'content_html',
        'revised_by',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
