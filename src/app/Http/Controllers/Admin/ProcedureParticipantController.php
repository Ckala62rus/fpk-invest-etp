<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureVisibility;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreProcedureParticipantRequest;
use App\Http\Requests\Api\Admin\UpdateProcedureParticipantRequest;
use App\Http\Resources\ProcedureParticipantResource;
use App\Models\Procedure;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Приглашения и статусы участников ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.5: особенно важно для закрытых (visibility=closed) процедур —
 * участвовать могут только приглашённые. Доступ как у ProcedureController.
 */
class ProcedureParticipantController extends ApiController
{
    /**
     * Список участников процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $participants = $procedure->participants()
            ->with('user:id,inn,email')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProcedureParticipantResource::collection($participants)->resolve(),
            'Участники процедуры.',
        );
    }

    /**
     * Приглашает участника (статус invited).
     *
     * @param StoreProcedureParticipantRequest $request user_id
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(StoreProcedureParticipantRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $userId = (int) $request->validated('user_id');
        $invitee = User::query()->findOrFail($userId);

        if (! $invitee->hasRole('participant')) {
            throw new DomainException(
                message: 'Приглашать можно только пользователей с ролью participant.',
                statusCode: 422,
            );
        }

        $exists = $procedure->participants()->where('user_id', $userId)->exists();
        if ($exists) {
            throw new DomainException(
                message: 'Этот участник уже добавлен в процедуру.',
                statusCode: 422,
            );
        }

        $participant = $procedure->participants()->create([
            'user_id' => $userId,
            'status' => ParticipantStatus::Invited,
        ]);

        $participant->load('user:id,inn,email');

        return $this->created(
            new ProcedureParticipantResource($participant),
            $procedure->visibility === ProcedureVisibility::Closed
                ? 'Участник приглашён в закрытую процедуру.'
                : 'Участник добавлен в процедуру.',
        );
    }

    /**
     * Меняет статус участника (допуск / отклонение / снова invited).
     *
     * @param UpdateProcedureParticipantRequest $request Новый статус
     * @param Procedure $procedure Родительская ТЗП
     * @param int $participant ID записи участника
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function update(
        UpdateProcedureParticipantRequest $request,
        Procedure $procedure,
        int $participant,
    ): JsonResponse {
        $this->assertCanAccess($procedure);

        /** @var User $admin */
        $admin = $request->user();
        $model = $this->findParticipantOrFail($procedure, $participant);
        $data = $request->validated();
        $status = ParticipantStatus::from($data['status']);

        $payload = [
            'status' => $status,
            'rejection_reason' => null,
            'admitted_at' => null,
            'admitted_by' => null,
        ];

        if ($status === ParticipantStatus::Admitted) {
            $payload['admitted_at'] = now();
            $payload['admitted_by'] = $admin->id;
        }

        if ($status === ParticipantStatus::Rejected) {
            $payload['rejection_reason'] = $data['rejection_reason'] ?? null;
        }

        $model->update($payload);
        $model->load('user:id,inn,email');

        return $this->success(
            new ProcedureParticipantResource($model->refresh()),
            'Статус участника обновлён.',
        );
    }

    /**
     * Удаляет приглашение участника из процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $participant ID записи
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function destroy(Procedure $procedure, int $participant): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $model = $this->findParticipantOrFail($procedure, $participant);
        $model->delete();

        return $this->success(
            null,
            'Участник удалён из процедуры.',
        );
    }

    /**
     * Находит запись участника процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $participantId ID записи
     * @return ProcedureParticipant
     *
     * @throws NotFoundHttpException
     */
    private function findParticipantOrFail(Procedure $procedure, int $participantId): ProcedureParticipant
    {
        $participant = $procedure->participants()->whereKey($participantId)->first();

        if ($participant === null) {
            throw new NotFoundHttpException('Участник процедуры не найден.');
        }

        return $participant;
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
