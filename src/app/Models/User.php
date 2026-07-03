<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Пользователь ЭТП.
 *
 * @property int $id Идентификатор
 * @property string $inn ИНН, логин при входе
 * @property string $email Основной email
 * @property UserStatus $status Статус учётной записи
 * @property \Illuminate\Support\Carbon|null $blocked_until Период блокировки до
 * @property string|null $block_reason Причина блокировки
 * @property int $failed_login_attempts Счётчик неудачных входов
 * @property \Illuminate\Support\Carbon|null $email_verified_at Подтверждение email
 * @property \Illuminate\Support\Carbon|null $approved_at Дата активации администратором
 * @property int|null $approved_by Кто одобрил регистрацию
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles Роли RBAC (Spatie HasRoles)
 */
#[Fillable([
    'inn',
    'email',
    'password',
    'status',
    'blocked_until',
    'block_reason',
    'failed_login_attempts',
    'email_verified_at',
    'approved_at',
    'approved_by',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Преобразование атрибутов учётной записи в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'blocked_until' => 'datetime',
            'password' => 'hashed',
            'failed_login_attempts' => 'integer',
        ];
    }

    /**
     * Профиль пользователя с данными регистрации (наименование, телефон, руководитель).
     *
     * Нужен для отображения карточки участника, ЛК и проверки полноты данных при допуске к ТЗП.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Дополнительные email-адреса пользователя, указанные при регистрации.
     *
     * Используется для рассылок и проверки дубликатов email при регистрации новых участников.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(UserEmail::class);
    }

    /**
     * Учредительные и регистрационные документы пользователя.
     *
     * Нужен администратору при модерации регистрации и для контроля срока действия документов (1 год).
     */
    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * Учредительные и регистрационные документы пользователя.
     *
     * Нужен для аудита: кто активировал учётную запись участника.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    /**
     * Настройки email-оповещений пользователя (категории, аукционы, напоминания).
     *
     * Используется при формировании рассылок, чтобы не отправлять письма отписавшимся участникам.
     */
    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    /**
     * Категории классификатора, на которые подписан участник.
     *
     * По подпискам определяется, о каких новых ТЗП и аукционах участник получает email-уведомления.
     */
    public function categorySubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(ClassifierCategory::class, 'user_category_subscriptions');
    }

    /**
     * Группы компаний холдинга, на которые подписан участник.
     *
     * Позволяет получать оповещения о закупках конкретных предприятий холдинга (1-й уровень классификатора).
     */
    public function companyGroupSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(CompanyGroup::class, 'user_company_group_subscriptions');
    }
}
