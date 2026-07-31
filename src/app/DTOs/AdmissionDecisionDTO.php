<?php

namespace App\DTOs;

use App\Enums\AdmissionDecision as AdmissionDecisionEnum;
use App\Http\Requests\Api\Admin\StoreAdmissionDecisionRequest;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * DTO (Data Transfer Object) решения о допуске / недопуске КП.
 */
readonly class AdmissionDecisionDTO
{
    /**
     * @param Procedure $procedure Родительская ТЗП
     * @param Proposal $proposal Заявка участника
     * @param User $decidedBy Администратор, принимающий решение
     * @param AdmissionDecisionEnum $decision admit или reject
     * @param string $reason Причина решения (обязательна по ТЗ)
     * @param Carbon|null $clarificationDeadline Срок уточнения КП (опционально)
     * @return void
     */
    public function __construct(
        public Procedure $procedure,
        public Proposal $proposal,
        public User $decidedBy,
        public AdmissionDecisionEnum $decision,
        public string $reason,
        public ?Carbon $clarificationDeadline = null,
    ) {
    }

    /**
     * Собирает DTO из маршрута и FormRequest.
     *
     * @param Procedure $procedure Процедура из маршрута
     * @param Proposal $proposal Заявка из маршрута
     * @param User $decidedBy Текущий администратор
     * @param StoreAdmissionDecisionRequest $request Валидированные данные
     * @return self
     */
    public static function fromRequest(
        Procedure $procedure,
        Proposal $proposal,
        User $decidedBy,
        StoreAdmissionDecisionRequest $request,
    ): self {
        $deadline = $request->validated('clarification_deadline');

        return new self(
            procedure: $procedure,
            proposal: $proposal,
            decidedBy: $decidedBy,
            decision: AdmissionDecisionEnum::from((string) $request->validated('decision')),
            reason: (string) $request->validated('reason'),
            clarificationDeadline: is_string($deadline) && $deadline !== ''
                ? Carbon::parse($deadline)
                : null,
        );
    }
}
