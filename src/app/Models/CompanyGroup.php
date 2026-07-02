<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Группа компаний холдинга (1-й уровень классификатора).
 *
 * @property int $id Идентификатор
 * @property string $name Название группы компаний
 * @property int $sort_order Порядок сортировки
 * @property bool $is_active Активна ли группа
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 */
class CompanyGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * Преобразование атрибутов группы компаний в типы PHP.
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
     * Категории предмета закупки (2-й уровень классификатора) внутри группы.
     *
     * Нужен для построения справочника категорий СМР, ПИР, ИТ и подписки участников на оповещения.
     */
    public function classifierCategories(): HasMany
    {
        return $this->hasMany(ClassifierCategory::class);
    }

    /**
     * Предприятия-заказчики, входящие в группу компаний холдинга.
     *
     * Используется для управления структурой заказчиков и привязки процедур к предприятиям.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Участники, подписанные на оповещения по процедурам этой группы компаний.
     *
     * Нужен для рассылки уведомлений о новых торгах в рамках выбранного холдинга.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_group_subscriptions');
    }
}
