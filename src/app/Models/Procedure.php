<?php

namespace App\Models;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\TradeDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Торгово-закупочная процедура (КП или аукцион).
 *
 * @property int $id Идентификатор
 * @property string $number Номер процедуры
 * @property ProcedureType $type Тип ТЗП
 * @property TradeDirection|null $trade_direction Направление торгов
 * @property string $title Название
 * @property string|null $description Описание
 * @property ProcedureStatus $status Статус процедуры
 * @property ProcedureVisibility $visibility Открытость процедуры
 * @property int $company_id Заказчик
 * @property int $classifier_category_id Категория закупки
 * @property int $responsible_user_id Ответственный администратор торгов
 * @property int $created_by Кто создал процедуру
 * @property string|null $customer_contact_name ФИО заказчика (скрыто от участников)
 * @property string|null $customer_contact_email Email заказчика (скрыто от участников)
 * @property \Illuminate\Support\Carbon|null $starts_at Дата и время начала
 * @property \Illuminate\Support\Carbon|null $ends_at Дата и время окончания
 * @property \Illuminate\Support\Carbon|null $published_at Дата публикации
 * @property \Illuminate\Support\Carbon|null $completed_at Дата завершения
 * @property bool $results_published Итоги опубликованы на странице ТЗП
 * @property int $storage_years Срок хранения заявок (лет)
 * @property int|null $source_procedure_id Связь КП → аукцион (2-й этап)
 * @property int|null $deleted_by Кто удалил процедуру
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Procedure extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'type',
        'trade_direction',
        'title',
        'description',
        'status',
        'visibility',
        'company_id',
        'classifier_category_id',
        'responsible_user_id',
        'created_by',
        'customer_contact_name',
        'customer_contact_email',
        'starts_at',
        'ends_at',
        'published_at',
        'completed_at',
        'results_published',
        'storage_years',
        'source_procedure_id',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProcedureType::class,
            'trade_direction' => TradeDirection::class,
            'status' => ProcedureStatus::class,
            'visibility' => ProcedureVisibility::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'completed_at' => 'datetime',
            'results_published' => 'boolean',
            'storage_years' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClassifierCategory::class, 'classifier_category_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function sourceProcedure(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_procedure_id');
    }

    public function childProcedures(): HasMany
    {
        return $this->hasMany(self::class, 'source_procedure_id');
    }

    public function auctionSetting(): HasOne
    {
        return $this->hasOne(AuctionSetting::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ProcedureLot::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ProcedureCustomField::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcedureDocument::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProcedureParticipant::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ProcedureChangeLog::class);
    }

    public function extraConditionValues(): HasMany
    {
        return $this->hasMany(ProcedureExtraConditionValue::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function auctionSessions(): HasMany
    {
        return $this->hasMany(AuctionSession::class);
    }

    public function auctionProtocols(): HasMany
    {
        return $this->hasMany(AuctionProtocol::class);
    }
}
