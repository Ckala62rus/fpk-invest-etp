<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SendExternalInvitesAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreExternalInviteRequest;
use App\Http\Resources\ExternalInviteBatchResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Внешние email-приглашения на процедуру (фаза 6.8).
 */
class ExternalInviteController extends ApiController
{
    /**
     * Запускает рассылку приглашений на указанные email.
     *
     * @param StoreExternalInviteRequest $request Список emails
     * @param Procedure $procedure Целевая ТЗП
     * @param SendExternalInvitesAction $action Бизнес-операция
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(
        StoreExternalInviteRequest $request,
        Procedure $procedure,
        SendExternalInvitesAction $action,
    ): JsonResponse {
        $this->assertCanManage($procedure);

        /** @var User $admin */
        $admin = $request->user();

        /** @var list<string> $emails */
        $emails = $request->validated('emails');

        $batch = $action->execute($procedure, $emails, $admin);

        return $this->created(
            new ExternalInviteBatchResource($batch),
            'Рассылка внешних приглашений запущена.',
        );
    }

    /**
     * @param Procedure $procedure ТЗП
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

        throw new AccessDeniedHttpException('Недостаточно прав для рассылки приглашений.');
    }
}
