<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ProcedureChangeLogResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * История изменений ТЗП (procedure_change_logs).
 *
 * Фаза 5.9: просмотр журнала правок черновика / документации.
 */
class ProcedureChangeLogController extends ApiController
{
    /**
     * Список записей изменений процедуры (новые сверху).
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $logs = $procedure->changeLogs()
            ->with('changedByUser:id,inn,email')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            ProcedureChangeLogResource::collection($logs)->resolve(),
            'История изменений процедуры.',
        );
    }

    /**
     * trade_admin без super_admin — только свои ТЗП.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanAccess(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasRole('super_admin') || $user->hasRole('auditor')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для доступа к этой процедуре.');
    }
}
