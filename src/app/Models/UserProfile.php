<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Профиль пользователя (данные регистрации).
 *
 * @property int $id Идентификатор
 * @property int $user_id Пользователь
 * @property EntityType $entity_type Тип субъекта: юрлицо или физлицо
 * @property string $name Наименование организации или ФИО
 * @property string $phone Контактный телефон
 * @property string $director_name ФИО руководителя
 * @property \Illuminate\Support\Carbon|null $director_birth_date Дата рождения руководителя
 * @property string $contact_persons Контактные лица
 * @property \Illuminate\Support\Carbon $pd_consent_at Дата согласия на обработку персональных данных
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'entity_type',
        'name',
        'phone',
        'director_name',
        'director_birth_date',
        'contact_persons',
        'pd_consent_at',
    ];

    /**
     * Преобразование атрибутов профиля пользователя в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
            'director_birth_date' => 'date',
            'pd_consent_at' => 'datetime',
        ];
    }

    /**
     * Учётная запись пользователя, к которой относится профиль.
     *
     * Нужен для отображения данных регистрации в карточке участника и проверки полноты профиля при допуске.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
