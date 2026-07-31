<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateProcedureAction;
use App\Actions\Admin\DeleteProcedureAction;
use App\Actions\Admin\PublishProcedureAction;
use App\Actions\Admin\RestoreProcedureAction;
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
 * Админский CRUD ТЗП (торгово-закупочных процедур) ЭТП.
 *
 * Фазы 5.1 / 5.7 / 5.8: черновики, публикация, soft delete и restore.
 * Роли: super_admin | trade_admin (запись); auditor — чтение.
 * trade_admin работает только со своими процедурами (responsible_user_id).
 * Удаление/восстановление — только super_admin (по матрице permissions).
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
     * @var PublishProcedureAction
     */
    private readonly PublishProcedureAction $publishProcedure;

    /**
     * @var DeleteProcedureAction
     */
    private readonly DeleteProcedureAction $deleteProcedure;

    /**
     * @var RestoreProcedureAction
     */
    private readonly RestoreProcedureAction $restoreProcedure;

    /**
     * @param ProcedureRepositoryInterface $procedures Репозиторий
     * @param CreateProcedureAction $createProcedure Создание черновика
     * @param UpdateProcedureAction $updateProcedure Обновление черновика
     * @param PublishProcedureAction $publishProcedure Публикация
     * @param DeleteProcedureAction $deleteProcedure Soft delete
     * @param RestoreProcedureAction $restoreProcedure Восстановление
     * @return void
     */
    public function __construct(
        ProcedureRepositoryInterface $procedures,
        CreateProcedureAction $createProcedure,
        UpdateProcedureAction $updateProcedure,
        PublishProcedureAction $publishProcedure,
        DeleteProcedureAction $deleteProcedure,
        RestoreProcedureAction $restoreProcedure,
    ) {
        $this->procedures = $procedures;
        $this->createProcedure = $createProcedure;
        $this->updateProcedure = $updateProcedure;
        $this->publishProcedure = $publishProcedure;
        $this->deleteProcedure = $deleteProcedure;
        $this->restoreProcedure = $restoreProcedure;
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
     * Обновляет черновик ТЗП (с записью в change log).
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

        /** @var User $editor */
        $editor = $request->user();

        $updated = $this->updateProcedure->execute($procedure, $request->validated(), $editor);

        return $this->success(
            new ProcedureResource($updated),
            'Процедура обновлена.',
        );
    }

    /**
     * Публикует черновик ТЗП и запускает рассылку.
     *
     * @param Procedure $procedure Черновик
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function publish(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        /** @var User $publisher */
        $publisher = request()->user();

        $published = $this->publishProcedure->execute($procedure, $publisher);

        return $this->success(
            new ProcedureResource($published),
            'Процедура опубликована.',
        );
    }

    /**
     * Мягко удаляет процедуру (папка «удалённые»).
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function destroy(Procedure $procedure): JsonResponse
    {
        $this->assertCanDelete($procedure);

        /** @var User $deleter */
        $deleter = request()->user();

        $this->deleteProcedure->execute($procedure, $deleter);

        return $this->success(
            null,
            'Процедура перемещена в удалённые.',
        );
    }

    /**
     * Восстанавливает процедуру из удалённых.
     *
     * @param int $procedure ID (в т.ч. soft-deleted)
     * @return JsonResponse
     *
     * @throws NotFoundHttpException|AccessDeniedHttpException|DomainException
     */
    public function restore(int $procedure): JsonResponse
    {
        $model = Procedure::withTrashed()->find($procedure);

        if ($model === null) {
            throw new NotFoundHttpException('Процедура не найдена.');
        }

        $this->assertCanDelete($model);

        /** @var User $restorer */
        $restorer = request()->user();

        $restored = $this->restoreProcedure->execute($model, $restorer);

        return $this->success(
            new ProcedureResource($restored),
            'Процедура восстановлена.',
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

    /**
     * Удаление/восстановление — только super_admin (матрица RBAC).
     *
     * @param Procedure $procedure Целевая процедура
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanDelete(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null || ! $user->hasRole('super_admin')) {
            throw new AccessDeniedHttpException('Удалять и восстанавливать процедуры может только super_admin.');
        }
    }
}
