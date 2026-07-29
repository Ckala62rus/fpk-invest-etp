<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateProcedureAction;
use App\Actions\Admin\UpdateProcedureAction;
use App\Contracts\ProcedureRepositoryInterface;
use App\DTOs\ProcedureFilterDTO;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListProceduresRequest;
use App\Http\Requests\Api\Admin\StoreProcedureRequest;
use App\Http\Requests\Api\Admin\UpdateProcedureRequest;
use App\Http\Resources\ProcedureResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Админский CRUD черновиков ТЗП (торгово-закупочных процедур) ЭТП.
 *
 * Фаза 5.1: создание/просмотр/редактирование черновиков (КП и аукцион).
 * Роли: super_admin | trade_admin (запись); auditor — только чтение списка/карточки.
 * trade_admin видит и правит только свои процедуры (responsible_user_id).
 */
class ProcedureController extends ApiController
{
    /**
     * @var ProcedureRepositoryInterface
     */
    private readonly ProcedureRepositoryInterface $procedures;

    /**
     * @var CreateProcedureAction
     */
    private readonly CreateProcedureAction $createProcedure;

    /**
     * @var UpdateProcedureAction
     */
    private readonly UpdateProcedureAction $updateProcedure;

    /**
     * @param ProcedureRepositoryInterface $procedures Репозиторий
     * @param CreateProcedureAction $createProcedure Создание черновика
     * @param UpdateProcedureAction $updateProcedure Обновление черновика
     * @return void
     */
    public function __construct(
        ProcedureRepositoryInterface $procedures,
        CreateProcedureAction $createProcedure,
        UpdateProcedureAction $updateProcedure,
    ) {
        $this->procedures = $procedures;
        $this->createProcedure = $createProcedure;
        $this->updateProcedure = $updateProcedure;
    }

    /**
     * Постраничный список ТЗП в админке.
     *
     * @param ListProceduresRequest $request Фильтры
     * @return JsonResponse
     */
    public function index(ListProceduresRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $forceResponsible = $user->hasRole('trade_admin') && ! $user->hasRole('super_admin')
            ? $user->id
            : null;

        $paginator = $this->procedures->paginateAdmin(
            ProcedureFilterDTO::fromRequest($request, $forceResponsible),
        );

        $paginator->through(
            static fn (Procedure $procedure): array => (new ProcedureResource($procedure))->resolve(),
        );

        return $this->paginated($paginator, 'Список процедур.');
    }

    /**
     * Карточка ТЗП для администратора.
     *
     * @param ListProceduresRequest $request Для доступа к user (не используется для валидации)
     * @param int $procedure ID процедуры
     * @return JsonResponse
     *
     * @throws NotFoundHttpException|AccessDeniedHttpException
     */
    public function show(int $procedure): JsonResponse
    {
        $model = $this->procedures->findAdminById($procedure);

        if ($model === null) {
            throw new NotFoundHttpException('Процедура не найдена.');
        }

        $this->assertCanAccess($model);

        return $this->success(
            new ProcedureResource($model),
            'Процедура.',
        );
    }

    /**
     * Создаёт черновик ТЗП (КП или аукцион).
     *
     * @param StoreProcedureRequest $request Валидированные данные
     * @return JsonResponse
     */
    public function store(StoreProcedureRequest $request): JsonResponse
    {
        /** @var User $author */
        $author = $request->user();

        $data = $request->validated();

        // trade_admin без super_admin всегда назначает себя ответственным
        if ($author->hasRole('trade_admin') && ! $author->hasRole('super_admin')) {
            $data['responsible_user_id'] = $author->id;
        }

        $procedure = $this->createProcedure->execute($data, $author);

        return $this->created(
            new ProcedureResource($procedure),
            'Черновик процедуры создан.',
        );
    }

    /**
     * Обновляет черновик ТЗП.
     *
     * @param UpdateProcedureRequest $request Валидированные поля
     * @param Procedure $procedure Целевая процедура
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function update(UpdateProcedureRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $updated = $this->updateProcedure->execute($procedure, $request->validated());

        return $this->success(
            new ProcedureResource($updated),
            'Процедура обновлена.',
        );
    }

    /**
     * trade_admin без super_admin может работать только со своими ТЗП.
     *
     * @param Procedure $procedure Целевая процедура
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
