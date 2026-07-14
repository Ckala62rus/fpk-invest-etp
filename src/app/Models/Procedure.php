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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
    use LogsActivity;
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

    /**
     * Преобразование атрибутов процедуры в типы PHP и enum.
     *
     * @return array<string, string>
     */
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

    /**
     * Настройки аудита изменений процедуры (только изменившиеся поля).
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('procedure')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Предприятие-заказчик, от имени которого проводится процедура.
     *
     * Отображается в карточке ТЗП на главной и в отчётах; участник видит заказчика, но не контакты.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Категория предмета закупки (2-й уровень классификатора: СМР, ПИР, ИТ и т.д.).
     *
     * Используется для фильтрации процедур на главной и для рассылок участникам по подпискам.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ClassifierCategory::class, 'classifier_category_id');
    }

    /**
     * Администратор торгов, ответственный за ведение этой процедуры.
     *
     * Нужен для делегирования, уведомлений и ограничения доступа trade_admin только к своим ТЗП.
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Пользователь, создавший черновик процедуры.
     *
     * Фиксируется для аудита: кто инициировал закупку на площадке.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Администратор, выполнивший мягкое удаление процедуры.
     *
     * Нужен для аудита действий в папке «удалённые»; данные процедуры сохраняются в БД.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Исходная процедура запроса предложений (1-й этап), если аукцион — 2-й этап.
     *
     * Связывает КП и последующий аукцион; используется для допуска только участников 1-го этапа.
     */
    public function sourceProcedure(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_procedure_id');
    }

    /**
     * Процедуры 2-го этапа (аукционы), созданные на основе этой процедуры КП.
     *
     * Позволяет из карточки запроса предложений перейти к связанному аукциону.
     */
    public function childProcedures(): HasMany
    {
        return $this->hasMany(self::class, 'source_procedure_id');
    }

    /**
     * Настройки электронного аукциона (шаг ставки, продление, режим «в ПЛЮС» и т.д.).
     *
     * Существует только для процедур типа «аукцион»; загружается при открытии торговой сессии.
     */
    public function auctionSetting(): HasOne
    {
        return $this->hasOne(AuctionSetting::class);
    }

    /**
     * Лоты многолотового аукциона с начальной ценой и шагом ставки.
     *
     * Каждый лот торгуется отдельно; по лотам определяются победители и история ставок.
     */
    public function lots(): HasMany
    {
        return $this->hasMany(ProcedureLot::class);
    }

    /**
     * Настраиваемые поля формы заявки или карточки процедуры.
     *
     * Администратор задаёт дополнительные поля, которые участник заполняет при подаче КП.
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(ProcedureCustomField::class);
    }

    /**
     * Конкурсная документация процедуры (ТЗ, извещения, приложения).
     *
     * Участники скачивают документы; изменения версий фиксируются в changeLogs с согласованием аудитора.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProcedureDocument::class);
    }

    /**
     * Участники, приглашённые или допущенные к закрытой процедуре.
     *
     * Для закрытых ТЗП определяет, кто может подать заявку или участвовать в аукционе.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ProcedureParticipant::class);
    }

    /**
     * История изменений документации с согласованием аудитором.
     *
     * Нужна для аудита, продления срока приёмки и уведомления участников об изменениях.
     */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(ProcedureChangeLog::class);
    }

    /**
     * Значения дополнительных условий аукциона (отсрочка, доставка и т.д.) для этой процедуры.
     *
     * Отображаются участникам в карточке аукциона перед подачей ставок.
     */
    public function extraConditionValues(): HasMany
    {
        return $this->hasMany(ProcedureExtraConditionValue::class);
    }

    /**
     * Заявки участников на запрос предложений (коммерческие предложения).
     *
     * Используется для приёма КП, допуска/недопуска и переписки по уточнениям.
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Все ставки участников по процедуре-аукциону (включая отменённые).
     *
     * Администратор видит полную историю; участник — только свои ставки через Policy.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    /**
     * Сессии посещения страницы аукциона (кто онлайн, кто не заходил).
     *
     * Нужен администратору для мониторинга активности приглашённых участников в реальном времени.
     */
    public function auctionSessions(): HasMany
    {
        return $this->hasMany(AuctionSession::class);
    }

    /**
     * Сгенерированные PDF-протоколы по результатам аукциона.
     *
     * Хранит ссылки на файлы протоколов для скачивания после завершения торгов.
     */
    public function auctionProtocols(): HasMany
    {
        return $this->hasMany(AuctionProtocol::class);
    }
}
