<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Категория предмета закупки (2-й уровень классификатора).
 *
 * @property int $id Идентификатор
 * @property int $company_group_id Группа компаний
 * @property string $name Категория: СМР, ПИР, ИТ и т.д.
 * @property int $sort_order Порядок сортировки
 * @property bool $is_active Активна ли категория
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 */
class ClassifierCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_group_id',
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * Преобразование атрибутов категории классификатора в типы PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Группа компаний холдинга, к которой относится категория.
     *
     * Нужен для построения двухуровневого классификатора и фильтрации процедур по холдингу.
     */
    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    /**
     * Участники, подписанные на оповещения по этой категории закупок.
     *
     * Используется для рассылки уведомлений о новых ТЗП в выбранных категориях (СМР, ПИР, ИТ и т.д.).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_category_subscriptions');
    }
}
