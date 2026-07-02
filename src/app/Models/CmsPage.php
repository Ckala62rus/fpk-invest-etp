<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Информационная страница CMS (О площадке, Правила и т.д.).
 *
 * @property int $id Идентификатор страницы
 * @property string $slug URL-slug страницы
 * @property string $title Заголовок страницы
 * @property string|null $meta_title SEO title
 * @property string|null $meta_description SEO description
 * @property bool $is_published Опубликована ли страница
 * @property int $sort_order Порядок в меню
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата изменения
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 */
class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'is_published',
        'sort_order',
    ];

    /**
     * Преобразование атрибутов страницы CMS в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Все ревизии (версии) содержимого страницы.
     *
     * Нужен для хранения истории редактирования и отката к предыдущей версии контента.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CmsPageRevision::class, 'page_id');
    }
}
