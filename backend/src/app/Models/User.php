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
    use HasFactory, Notifiable, SoftDeletes;

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

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(UserEmail::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    public function categorySubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(ClassifierCategory::class, 'user_category_subscriptions');
    }

    public function companyGroupSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(CompanyGroup::class, 'user_company_group_subscriptions');
    }
}
