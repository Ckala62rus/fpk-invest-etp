<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\AdminProposalResource;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalVisibilityService;
use App\Support\ProposalAccessLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Просмотр заявок (КП) администратором с правилами видимости (фаза 6.5).
 */
class AdminProposalController extends ApiController
{
    /**
     * @param ProposalVisibilityService $visibility Сервис видимости
     * @return void
     */
    public function __construct(
        private readonly ProposalVisibilityService $visibility,
    ) {
    }

    /**
     * Список заявок процедуры (до дедлайна — только имена участников).
     *
     * @param Request $request HTTP-запрос
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Request $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $proposals = $procedure->proposals()
            ->with(['user.profile', 'procedure'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            AdminProposalResource::collection($proposals)->resolve(),
            'Заявки по процедуре.',
        );
    }

    /**
     * Карточка заявки; после дедлайна — полное КП + запись в access log.
     *
     * @param Request $request HTTP-запрос
     * @param Procedure $procedure Родительская ТЗП
     * @param int $proposal ID заявки
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function show(Request $request, Procedure $procedure, int $proposal): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $model = Proposal::query()
            ->whereKey($proposal)
            ->where('procedure_id', $procedure->id)
            ->with([
                'user.profile',
                'procedure',
                'fieldValues.customField',
                'documents',
                'admissionDecision',
            ])
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Заявка не найдена в этой процедуре.');
        }

        /** @var User $user */
        $user = $request->user();

        if (! $this->visibility->canView($user, $model)) {
            throw new AccessDeniedHttpException('Недостаточно прав для просмотра заявки.');
        }

        if ($this->visibility->canViewFullContent($user, $model)) {
            ProposalAccessLogger::log($model, $user, $request, 'view');
        }

        return $this->success(
            new AdminProposalResource($model),
            'Заявка участника.',
        );
    }

    /**
     * @param Procedure $procedure ТЗП
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
