<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AdmissionDecisionAction;
use App\DTOs\AdmissionDecisionDTO;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreAdmissionDecisionRequest;
use App\Http\Resources\AdmissionDecisionResource;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Решение о допуске / недопуске коммерческого предложения (КП).
 *
 * Фаза 6.3: только super_admin | trade_admin (auditor — только чтение других endpoint’ов).
 * trade_admin — только свои процедуры (responsible_user_id).
 */
class ProposalAdmissionController extends ApiController
{
    /**
     * Фиксирует допуск или недопуск заявки.
     *
     * @param StoreAdmissionDecisionRequest $request decision + reason
     * @param Procedure $procedure Родительская ТЗП
     * @param int $proposal ID заявки
     * @param AdmissionDecisionAction $action Бизнес-операция
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function store(
        StoreAdmissionDecisionRequest $request,
        Procedure $procedure,
        int $proposal,
        AdmissionDecisionAction $action,
    ): JsonResponse {
        $this->assertCanManage($procedure);

        $model = Proposal::query()
            ->whereKey($proposal)
            ->where('procedure_id', $procedure->id)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Заявка не найдена в этой процедуре.');
        }

        /** @var User $admin */
        $admin = $request->user();

        $decision = $action->execute(
            AdmissionDecisionDTO::fromRequest($procedure, $model, $admin, $request),
        );

        return $this->created(
            new AdmissionDecisionResource($decision),
            $decision->decision?->value === 'admit'
                ? 'Заявка допущена.'
                : 'Заявка отклонена.',
        );
    }

    /**
     * Запись по процедуре: super_admin или ответственный trade_admin.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanManage(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для решения о допуске по этой процедуре.');
    }
}
