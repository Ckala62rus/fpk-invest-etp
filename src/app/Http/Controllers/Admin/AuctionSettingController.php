<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateAuctionSettingAction;
use App\Enums\ProcedureType;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\UpdateAuctionSettingRequest;
use App\Http\Resources\AuctionSettingResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API настроек электронного аукциона (фаза 8.1).
 *
 * GET — просмотр; PUT — обновление до старта торгов.
 * Доступ: super_admin|trade_admin (свои)|auditor (только чтение).
 */
class AuctionSettingController extends ApiController
{
    /**
     * @param UpdateAuctionSettingAction $updateAction Действие обновления настроек
     * @return void
     */
    public function __construct(
        private readonly UpdateAuctionSettingAction $updateAction,
    ) {
    }

    /**
     * Возвращает настройки аукциона процедуры.
     *
     * @param Procedure $procedure Процедура
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function show(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertAuction($procedure);

        $settings = $procedure->auctionSetting;

        if ($settings === null) {
            throw new NotFoundHttpException('Настройки аукциона не найдены.');
        }

        return $this->success(
            new AuctionSettingResource($settings),
            'Настройки аукциона.',
        );
    }

    /**
     * Обновляет настройки аукциона.
     *
     * @param UpdateAuctionSettingRequest $request Валидированные поля
     * @param Procedure $procedure Процедура-аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function update(UpdateAuctionSettingRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanWrite($procedure);

        /** @var User $actor */
        $actor = $request->user();

        $settings = $this->updateAction->execute(
            $procedure,
            $request->validated(),
            $actor,
        );

        return $this->success(
            new AuctionSettingResource($settings),
            'Настройки аукциона обновлены.',
        );
    }

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertAuction(Procedure $procedure): void
    {
        if ($procedure->type !== ProcedureType::Auction) {
            throw new DomainException(
                message: 'Настройки аукциона доступны только для процедур типа auction.',
                statusCode: 422,
            );
        }
    }

    /**
     * Чтение: super_admin, auditor, trade_admin (свои).
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

    /**
     * Запись: super_admin или ответственный trade_admin (auditor — только чтение).
     *
     * @param Procedure $procedure Целевая ТЗП
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

        throw new AccessDeniedHttpException('Недостаточно прав для изменения настроек аукциона.');
    }
}
