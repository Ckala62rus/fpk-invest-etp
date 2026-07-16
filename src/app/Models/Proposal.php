<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Заявка участника на запрос предложений.
 *
 * @property int $id Идентификатор
 * @property int $procedure_id Процедура
 * @property int $user_id Участник
 * @property ProposalStatus $status Статус заявки
 * @property \Illuminate\Support\Carbon|null $submitted_at Дата подачи заявки
 * @property \Illuminate\Support\Carbon|null $contract_form_agreed_at Согласие с формой договора
 * @property int $version Версия заявки после уточнения
 * @property int|null $parent_proposal_id Предыдущая версия заявки
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'procedure_id',
        'user_id',
        'status',
        'submitted_at',
        'contract_form_agreed_at',
        'version',
        'parent_proposal_id',
    ];

    /**
     * Преобразование атрибутов заявки в типы PHP и enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'submitted_at' => 'datetime',
            'contract_form_agreed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    /**
     * Процедура запроса предложений, на которую подана заявка.
     *
     * Нужен для отображения заявок в карточке процедуры и проверки сроков подачи.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Участник, подавший заявку.
     *
     * Используется для отображения данных заявителя; участники видят только свои заявки.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Предыдущая версия заявки до уточнения коммерческого предложения.
     *
     * Нужен для отслеживания истории версий заявки при процедуре уточнения КП.
     */
    public function parentProposal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_proposal_id');
    }

    /**
     * Последующие версии заявки, созданные после уточнения КП.
     *
     * Используется для навигации по цепочке версий и сравнения изменений.
     */
    public function childProposals(): HasMany
    {
        return $this->hasMany(self::class, 'parent_proposal_id');
    }

    /**
     * Значения настраиваемых полей, заполненных в этой заявке.
     *
     * Нужен для отображения ответов участника на дополнительные поля процедуры.
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(ProposalFieldValue::class);
    }

    /**
     * Документы, прикреплённые участником к заявке.
     *
     * Используется для скачивания КП и сопутствующих файлов при рассмотрении заявки.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProposalDocument::class);
    }

    /**
     * Решение о допуске или недопуске по этой заявке.
     *
     * Нужен для отображения статуса допуска в карточке заявки и списке участников.
     */
    public function admissionDecision(): HasOne
    {
        return $this->hasOne(AdmissionDecision::class);
    }

    /**
     * Переписка по уточнению коммерческого предложения.
     *
     * Используется для обмена сообщениями между администратором и участником при уточнении КП.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ProposalMessage::class);
    }

    /**
     * Журнал обращений к заявке (просмотры, скачивания).
     *
     * Нужен для аудита доступа к конфиденциальным данным заявки и контроля просмотров.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(ProposalAccessLog::class);
    }
}
