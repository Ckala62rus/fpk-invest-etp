<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\RejectProcedureChangeRequest;
use App\Http\Resources\ProcedureChangeLogResource;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\User;
use App\Services\ProcedureChangeApprovalService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Согласование изменений документации опубликованной ТЗП (фаза 6.6).
 */
class ProcedureChangeApprovalController extends ApiController
{
    /**
     * Согласует изменение (продлевает срок, уведомляет участников).
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $changeLog ID записи change log
     * @param ProcedureChangeApprovalService $service Сервис согласования
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function approve(
        Procedure $procedure,
        int $changeLog,
        ProcedureChangeApprovalService $service,
    ): JsonResponse {
        $this->assertCanApprove();

        $log = $this->findLogOrFail($procedure, $changeLog);

        /** @var User $approver */
        $approver = request()->user();

        $updated = $service->approve($log, $approver);

        return $this->success(
            new ProcedureChangeLogResource($updated),
            'Изменение документации согласовано.',
        );
    }

    /**
     * Отклоняет изменение документации.
     *
     * @param RejectProcedureChangeRequest $request Причина отклонения
     * @param Procedure $procedure Родительская ТЗП
     * @param int $changeLog ID записи
     * @param ProcedureChangeApprovalService $service Сервис согласования
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function reject(
        RejectProcedureChangeRequest $request,
        Procedure $procedure,
        int $changeLog,
        ProcedureChangeApprovalService $service,
    ): JsonResponse {
        $this->assertCanApprove();

        $log = $this->findLogOrFail($procedure, $changeLog);

        /** @var User $rejector */
        $rejector = request()->user();

        $updated = $service->reject($log, $rejector, (string) $request->validated('reason'));

        return $this->success(
            new ProcedureChangeLogResource($updated),
            'Изменение документации отклонено.',
        );
    }

    /**
     * @param Procedure $procedure ТЗП
     * @param int $changeLogId ID записи
     * @return ProcedureChangeLog
     *
     * @throws NotFoundHttpException
     */
    private function findLogOrFail(Procedure $procedure, int $changeLogId): ProcedureChangeLog
    {
        $log = ProcedureChangeLog::query()
            ->whereKey($changeLogId)
            ->where('procedure_id', $procedure->id)
            ->with('procedure')
            ->first();

        if ($log === null) {
            throw new NotFoundHttpException('Запись изменения не найдена.');
        }

        return $log;
    }

    /**
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanApprove(): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null || ! $user->hasAnyRole(['super_admin', 'auditor'])) {
            throw new AccessDeniedHttpException('Согласовывать изменения могут super_admin и auditor.');
        }
    }
}
