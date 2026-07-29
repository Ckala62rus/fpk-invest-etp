<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreProcedureLotRequest;
use App\Http\Requests\Api\Admin\UpdateProcedureLotRequest;
use App\Http\Resources\ProcedureLotResource;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD лотов ТЗП (торгово-закупочной процедуры), в т.ч. многолотовых аукционов.
 *
 * Фаза 5.3: изменение только у черновика; доступ как у ProcedureController.
 */
class ProcedureLotController extends ApiController
{
    /**
     * Список лотов процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $lots = $procedure->lots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProcedureLotResource::collection($lots)->resolve(),
            'Лоты процедуры.',
        );
    }

    /**
     * Создаёт лот у черновика ТЗП.
     *
     * @param StoreProcedureLotRequest $request Валидированные данные
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(StoreProcedureLotRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $data = $request->validated();
        $lot = $procedure->lots()->create([
            'name' => $data['name'],
            'unit' => $data['unit'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'start_price' => $data['start_price'],
            'bid_step' => $data['bid_step'],
            'sort_order' => $data['sort_order'] ?? 0,
            'current_price' => $data['start_price'],
        ]);

        return $this->created(
            new ProcedureLotResource($lot),
            'Лот создан.',
        );
    }

    /**
     * Обновляет лот черновика.
     *
     * @param UpdateProcedureLotRequest $request Валидированные поля
     * @param Procedure $procedure Родительская ТЗП
     * @param int $lot ID лота
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function update(
        UpdateProcedureLotRequest $request,
        Procedure $procedure,
        int $lot,
    ): JsonResponse {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $model = $this->findLotOrFail($procedure, $lot);
        $data = $request->validated();

        // Если меняют стартовую цену до торгов — синхронизируем current_price
        if (array_key_exists('start_price', $data) && $model->current_price === $model->start_price) {
            $data['current_price'] = $data['start_price'];
        }

        $model->update($data);

        return $this->success(
            new ProcedureLotResource($model->refresh()),
            'Лот обновлён.',
        );
    }

    /**
     * Удаляет лот черновика.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $lot ID лота
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function destroy(Procedure $procedure, int $lot): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $model = $this->findLotOrFail($procedure, $lot);
        $model->delete();

        return $this->success(
            null,
            'Лот удалён.',
        );
    }

    /**
     * Находит лот, принадлежащий процедуре.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $lotId ID лота
     * @return ProcedureLot
     *
     * @throws NotFoundHttpException
     */
    private function findLotOrFail(Procedure $procedure, int $lotId): ProcedureLot
    {
        $lot = $procedure->lots()->whereKey($lotId)->first();

        if ($lot === null) {
            throw new NotFoundHttpException('Лот не найден.');
        }

        return $lot;
    }

    /**
     * Лоты можно менять только у черновика.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertDraft(Procedure $procedure): void
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Лоты можно менять только у черновика процедуры.',
                statusCode: 422,
            );
        }
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
