<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\SyncProcedureExtraConditionsRequest;
use App\Http\Resources\ProcedureExtraConditionValueResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Значения дополнительных условий для конкретной ТЗП.
 *
 * Фаза 5.6: чтение и sync значений по шаблонам справочника.
 */
class ProcedureExtraConditionController extends ApiController
{
    /**
     * Список значений доп. условий процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $values = $procedure->extraConditionValues()
            ->with('template')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProcedureExtraConditionValueResource::collection($values)->resolve(),
            'Дополнительные условия процедуры.',
        );
    }

    /**
     * Полная синхронизация значений доп. условий у черновика.
     *
     * @param SyncProcedureExtraConditionsRequest $request conditions[]
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function sync(SyncProcedureExtraConditionsRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Дополнительные условия можно менять только у черновика процедуры.',
                statusCode: 422,
            );
        }

        $conditions = $request->validated('conditions');

        DB::transaction(function () use ($procedure, $conditions): void {
            $procedure->extraConditionValues()->delete();

            foreach ($conditions as $row) {
                $procedure->extraConditionValues()->create([
                    'template_id' => $row['template_id'],
                    'value' => $row['value'] ?? null,
                ]);
            }
        });

        $values = $procedure->extraConditionValues()
            ->with('template')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProcedureExtraConditionValueResource::collection($values)->resolve(),
            'Дополнительные условия сохранены.',
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
