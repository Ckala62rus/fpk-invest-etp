<?php

namespace App\Actions\Admin;

use App\DTOs\AdmissionDecisionDTO;
use App\Enums\AdmissionDecision as AdmissionDecisionEnum;
use App\Enums\ProposalStatus;
use App\Exceptions\DomainException;
use App\Models\AdmissionDecision;
use Illuminate\Support\Facades\DB;

/**
 * Фиксирует решение о допуске или недопуске коммерческого предложения (КП).
 *
 * Фаза 6.3: создаёт admission_decisions, обновляет статус заявки, пишет activity log.
 */
class AdmissionDecisionAction
{
    /**
     * Принимает решение по заявке.
     *
     * @param AdmissionDecisionDTO $dto Данные решения
     * @return AdmissionDecision Сохранённое решение с связями
     *
     * @throws DomainException Если нельзя принять решение
     */
    public function execute(AdmissionDecisionDTO $dto): AdmissionDecision
    {
        $this->assertCanDecide($dto);

        return DB::transaction(function () use ($dto): AdmissionDecision {
            $now = now();

            $record = AdmissionDecision::query()->create([
                'proposal_id' => $dto->proposal->id,
                'decision' => $dto->decision,
                'reason' => $dto->reason,
                'decided_by' => $dto->decidedBy->id,
                'decided_at' => $now,
                'clarification_deadline' => $dto->clarificationDeadline,
            ]);

            $newStatus = $dto->decision === AdmissionDecisionEnum::Admit
                ? ProposalStatus::Admitted
                : ProposalStatus::Rejected;

            $dto->proposal->update([
                'status' => $newStatus,
            ]);

            activity('proposal')
                ->causedBy($dto->decidedBy)
                ->performedOn($dto->proposal)
                ->event('admission_'.$dto->decision->value)
                ->withProperties([
                    'procedure_id' => $dto->procedure->id,
                    'decision' => $dto->decision->value,
                    'reason' => $dto->reason,
                ])
                ->log(
                    $dto->decision === AdmissionDecisionEnum::Admit
                        ? 'Заявка допущена'
                        : 'Заявка отклонена',
                );

            return $record->fresh(['decidedByUser:id,inn,email', 'proposal']) ?? $record;
        });
    }

    /**
     * Проверяет бизнес-правила допуска.
     *
     * @param AdmissionDecisionDTO $dto Данные решения
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanDecide(AdmissionDecisionDTO $dto): void
    {
        if ((int) $dto->proposal->procedure_id !== (int) $dto->procedure->id) {
            throw new DomainException(
                message: 'Заявка не относится к этой процедуре.',
                statusCode: 422,
            );
        }

        $allowedStatuses = [
            ProposalStatus::Submitted,
            ProposalStatus::UnderReview,
        ];

        if (! in_array($dto->proposal->status, $allowedStatuses, true)) {
            throw new DomainException(
                message: 'Решение о допуске можно принять только для поданной или рассматриваемой заявки.',
                statusCode: 422,
            );
        }

        if ($dto->proposal->admissionDecision()->exists()) {
            throw new DomainException(
                message: 'По этой заявке решение о допуске уже принято.',
                statusCode: 422,
            );
        }

        if (trim($dto->reason) === '') {
            throw new DomainException(
                message: 'Укажите причину решения о допуске или недопуске.',
                statusCode: 422,
                errors: [
                    'reason' => ['Укажите причину решения о допуске или недопуске.'],
                ],
            );
        }
    }
}
