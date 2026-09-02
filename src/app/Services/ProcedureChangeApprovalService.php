<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Events\ProcedureDocumentationChanged;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\ProcedureDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Согласование изменений документации опубликованной ТЗП (фаза 6.6).
 */
class ProcedureChangeApprovalService
{
    /**
     * @param SettingsService $settings Глобальные настройки
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Согласует изменение документации: продлевает ends_at, уведомляет участников.
     *
     * @param ProcedureChangeLog $changeLog Запись с pending
     * @param User $approver super_admin или auditor
     * @return ProcedureChangeLog
     *
     * @throws DomainException
     */
    public function approve(ProcedureChangeLog $changeLog, User $approver): ProcedureChangeLog
    {
        $this->assertCanDecide($changeLog, $approver);

        return DB::transaction(function () use ($changeLog, $approver): ProcedureChangeLog {
            $procedure = $changeLog->procedure;
            $extensionDays = $this->settings->rfpExtensionDays();
            $newEndsAt = ($procedure->ends_at ?? now())->copy()->addDays($extensionDays);

            $procedure->update(['ends_at' => $newEndsAt]);

            $changeLog->update([
                'approval_status' => ApprovalStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'deadline_extended_to' => $newEndsAt,
            ]);

            activity('procedure')
                ->causedBy($approver)
                ->performedOn($procedure)
                ->event('documentation_change_approved')
                ->withProperties([
                    'change_log_id' => $changeLog->id,
                    'deadline_extended_to' => $newEndsAt->toIso8601String(),
                ])
                ->log('Изменение документации согласовано');

            ProcedureDocumentationChanged::dispatch($procedure, $changeLog->id);

            $changeLog->update(['notifications_sent_at' => now()]);

            return $changeLog->fresh(['procedure', 'approvedByUser']) ?? $changeLog;
        });
    }

    /**
     * Отклоняет изменение; удаляет связанный документ (если указан в diff).
     *
     * @param ProcedureChangeLog $changeLog Запись с pending
     * @param User $rejector super_admin или auditor
     * @param string $reason Причина отклонения
     * @return ProcedureChangeLog
     *
     * @throws DomainException
     */
    public function reject(ProcedureChangeLog $changeLog, User $rejector, string $reason): ProcedureChangeLog
    {
        $this->assertCanDecide($changeLog, $rejector);

        return DB::transaction(function () use ($changeLog, $rejector, $reason): ProcedureChangeLog {
            $documentId = $changeLog->diff['document_id'] ?? null;

            if (is_numeric($documentId)) {
                ProcedureDocument::query()
                    ->whereKey((int) $documentId)
                    ->where('procedure_id', $changeLog->procedure_id)
                    ->delete();
            }

            $changeLog->update([
                'approval_status' => ApprovalStatus::Rejected,
                'approved_by' => $rejector->id,
                'approved_at' => now(),
                'change_summary' => $changeLog->change_summary.' (отклонено: '.$reason.')',
            ]);

            activity('procedure')
                ->causedBy($rejector)
                ->performedOn($changeLog->procedure)
                ->event('documentation_change_rejected')
                ->withProperties(['change_log_id' => $changeLog->id, 'reason' => $reason])
                ->log('Изменение документации отклонено');

            return $changeLog->fresh(['procedure', 'approvedByUser']) ?? $changeLog;
        });
    }

    /**
     * @param ProcedureChangeLog $changeLog Запись
     * @param User $user Решающий
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanDecide(ProcedureChangeLog $changeLog, User $user): void
    {
        if ($changeLog->approval_status !== ApprovalStatus::Pending) {
            throw new DomainException(
                message: 'Решение можно принять только по записи, ожидающей согласования.',
                statusCode: 422,
            );
        }

        if (! $user->hasAnyRole(['super_admin', 'auditor'])) {
            throw new DomainException(
                message: 'Согласовывать изменения документации могут super_admin и auditor.',
                statusCode: 403,
            );
        }

        $procedure = $changeLog->procedure;

        if ($procedure->status !== ProcedureStatus::Accepting) {
            throw new DomainException(
                message: 'Согласование изменений доступно только для процедуры в приёме заявок.',
                statusCode: 422,
            );
        }
    }
}
