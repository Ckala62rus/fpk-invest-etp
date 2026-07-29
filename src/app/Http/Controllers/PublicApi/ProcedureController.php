<?php

namespace App\Http\Controllers\PublicApi;

use App\Contracts\ProcedureRepositoryInterface;
use App\DTOs\PublicProcedureFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Public\ListPublicProceduresRequest;
use App\Http\Resources\PublicProcedureResource;
use App\Models\Procedure;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Публичный API списка и карточки ТЗП (торгово-закупочных процедур).
 *
 * Фаза 4.3: гость видит только открытые (visibility=open) опубликованные процедуры,
 * без контактов заказчика и без черновиков/закрытых.
 */
class ProcedureController extends ApiController
{
    /**
     * Репозиторий ТЗП.
     *
     * @var ProcedureRepositoryInterface
     */
    private readonly ProcedureRepositoryInterface $procedures;

    /**
     * @param ProcedureRepositoryInterface $procedures Репозиторий
     * @return void
     */
    public function __construct(ProcedureRepositoryInterface $procedures)
    {
        $this->procedures = $procedures;
    }

    /**
     * Постраничный публичный список открытых ТЗП с фильтрами.
     *
     * @param ListPublicProceduresRequest $request Фильтры поиска
     * @return JsonResponse
     */
    public function index(ListPublicProceduresRequest $request): JsonResponse
    {
        $paginator = $this->procedures->paginatePublic(
            PublicProcedureFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (Procedure $procedure): array => (new PublicProcedureResource($procedure))->resolve(),
        );

        return $this->paginated($paginator, 'Список открытых процедур.');
    }

    /**
     * Публичная карточка открытой ТЗП по ID.
     *
     * @param int $procedure Идентификатор процедуры
     * @return JsonResponse
     *
     * @throws NotFoundHttpException Если процедура недоступна гостю
     */
    public function show(int $procedure): JsonResponse
    {
        $model = $this->procedures->findPublicById($procedure);

        if ($model === null) {
            throw new NotFoundHttpException('Процедура не найдена.');
        }

        return $this->success(
            new PublicProcedureResource($model),
            'Процедура.',
        );
    }
}
