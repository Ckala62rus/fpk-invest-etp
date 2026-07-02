<?php

namespace App\Models;

use App\Enums\ProposalStatus;
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
    protected $fillable = [
        'procedure_id',
        'user_id',
        'status',
        'submitted_at',
        'contract_form_agreed_at',
        'version',
        'parent_proposal_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'submitted_at' => 'datetime',
            'contract_form_agreed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentProposal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_proposal_id');
    }

    public function childProposals(): HasMany
    {
        return $this->hasMany(self::class, 'parent_proposal_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(ProposalFieldValue::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProposalDocument::class);
    }

    public function admissionDecision(): HasOne
    {
        return $this->hasOne(AdmissionDecision::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProposalMessage::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ProposalAccessLog::class);
    }
}
