<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Proposal\SendProposalMessageAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\StoreProposalMessageRequest;
use App\Http\Resources\ProposalMessageResource;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Админская переписка и запрос уточнения по КП.
 *
 * Фаза 6.4: super_admin | trade_admin (свои ТЗП) | auditor (чтение).
 */
class ProposalMessageController extends ApiController
{
    /**
     * История сообщений по заявке процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $proposal ID заявки
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function index(Procedure $procedure, int $proposal): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $model = $this->findProposalOrFail($procedure, $proposal);

        $messages = $model->messages()
            ->with('sender.profile')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProposalMessageResource::collection($messages)->resolve(),
            'Переписка по заявке.',
        );
    }

    /**
     * Отправляет сообщение; опционально запрашивает уточнение (≥ 2 рабочих дня).
     *
     * @param StoreProposalMessageRequest $request Текст + флаги уточнения
     * @param Procedure $procedure Родительская ТЗП
     * @param int $proposal ID заявки
     * @param SendProposalMessageAction $action Бизнес-операция
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function store(
        StoreProposalMessageRequest $request,
        Procedure $procedure,
        int $proposal,
        SendProposalMessageAction $action,
    ): JsonResponse {
        $this->assertCanWrite($procedure);
        $model = $this->findProposalOrFail($procedure, $proposal);

        /** @var User $sender */
        $sender = $request->user();
        $data = $request->validated();

        $deadlineRaw = $data['clarification_deadline'] ?? null;
        $requestClarification = (bool) ($data['request_clarification'] ?? false);

        $message = $action->execute(
            proposal: $model->loadMissing('procedure', 'admissionDecision'),
            sender: $sender,
            message: (string) $data['message'],
            attachments: $data['attachments'] ?? null,
            requestClarification: $requestClarification,
            clarificationDeadline: is_string($deadlineRaw) && $deadlineRaw !== ''
                ? Carbon::parse($deadlineRaw)
                : null,
        );

        return $this->created(
            new ProposalMessageResource($message),
            $requestClarification
                ? 'Запрошено уточнение коммерческого предложения.'
                : 'Сообщение отправлено.',
        );
    }

    /**
     * @param Procedure $procedure ТЗП
     * @param int $proposalId ID заявки
     * @return Proposal
     *
     * @throws NotFoundHttpException
     */
    private function findProposalOrFail(Procedure $procedure, int $proposalId): Proposal
    {
        $model = Proposal::query()
            ->whereKey($proposalId)
            ->where('procedure_id', $procedure->id)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Заявка не найдена в этой процедуре.');
        }

        return $model;
    }

    /**
     * Чтение: super_admin, auditor, ответственный trade_admin.
     *
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

        throw new AccessDeniedHttpException('Недостаточно прав для просмотра переписки.');
    }

    /**
     * Запись: super_admin или ответственный trade_admin (не auditor).
     *
     * @param Procedure $procedure ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanWrite(Procedure $procedure): void
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

        throw new AccessDeniedHttpException('Недостаточно прав для отправки сообщения.');
    }
}
