<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ProcedureResource;
use App\Models\Procedure;
use App\Models\User;
use App\Services\AuctionSessionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Управление жизненным циклом торгов аукциона (фаза 8.2).
 *
 * start / pause / resume / finish — только super_admin и ответственный trade_admin.
 */
class AuctionLifecycleController extends ApiController
{
    /**
     * @param AuctionSessionService $sessions Сервис state machine торгов
     * @return void
     */
    public function __construct(
        private readonly AuctionSessionService $sessions,
    ) {
    }

    /**
     * Запускает торги (auction_pending → in_progress).
     *
     * @param Procedure $procedure Процедура-аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|\App\Exceptions\DomainException
     */
    public function start(Procedure $procedure): JsonResponse
    {
        $actor = $this->actor();
        $this->assertCanManage($procedure, $actor);

        $procedure = $this->sessions->start($procedure, $actor);

        return $this->success(
            new ProcedureResource($procedure),
            'Аукцион запущен.',
        );
    }

    /**
     * Ставит торги на паузу.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|\App\Exceptions\DomainException
     */
    public function pause(Procedure $procedure): JsonResponse
    {
        $actor = $this->actor();
        $this->assertCanManage($procedure, $actor);

        $procedure = $this->sessions->pause($procedure, $actor);

        return $this->success(
            new ProcedureResource($procedure),
            'Аукцион поставлен на паузу.',
        );
    }

    /**
     * Снимает паузу.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|\App\Exceptions\DomainException
     */
    public function resume(Procedure $procedure): JsonResponse
    {
        $actor = $this->actor();
        $this->assertCanManage($procedure, $actor);

        $procedure = $this->sessions->resume($procedure, $actor);

        return $this->success(
            new ProcedureResource($procedure),
            'Аукцион возобновлён.',
        );
    }

    /**
     * Завершает торги (in_progress → completed).
     *
     * @param Procedure $procedure Процедура-аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|\App\Exceptions\DomainException
     */
    public function finish(Procedure $procedure): JsonResponse
    {
        $actor = $this->actor();
        $this->assertCanManage($procedure, $actor);

        $procedure = $this->sessions->finish($procedure, $actor);

        return $this->success(
            new ProcedureResource($procedure),
            'Аукцион завершён.',
        );
    }

    /**
     * @return User
     *
     * @throws AccessDeniedHttpException
     */
    private function actor(): User
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        return $user;
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @param User $user Текущий пользователь
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanManage(Procedure $procedure, User $user): void
    {
        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для управления торгами.');
    }
}
