<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CompanyGroupRepositoryInterface;
use App\DTOs\CompanyGroupFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListCompanyGroupsRequest;
use App\Http\Requests\Api\Admin\StoreCompanyGroupRequest;
use App\Http\Requests\Api\Admin\UpdateCompanyGroupRequest;
use App\Http\Resources\CompanyGroupResource;
use App\Models\CompanyGroup;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD групп компаний холдинга (1-й уровень классификатора) ЭТП.
 *
 * Фаза 3.1: доступ только у super_admin.
 */
class CompanyGroupController extends ApiController
{
    /**
     * Репозиторий выборок групп компаний.
     *
     * @var CompanyGroupRepositoryInterface
     */
    private readonly CompanyGroupRepositoryInterface $companyGroups;

    /**
     * Создаёт контроллер групп компаний.
     *
     * @param CompanyGroupRepositoryInterface $companyGroups Репозиторий
     * @return void
     */
    public function __construct(CompanyGroupRepositoryInterface $companyGroups)
    {
        $this->companyGroups = $companyGroups;
    }

    /**
     * Возвращает постраничный список групп компаний.
     *
     * @param ListCompanyGroupsRequest $request Валидированные фильтры
     * @return JsonResponse JSON с data + meta
     */
    public function index(ListCompanyGroupsRequest $request): JsonResponse
    {
        $paginator = $this->companyGroups->paginate(
            CompanyGroupFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (CompanyGroup $group): array => (new CompanyGroupResource($group))->resolve(),
        );

        return $this->paginated($paginator, 'Список групп компаний.');
    }

    /**
     * Возвращает одну группу компаний по ID.
     *
     * @param int $companyGroup Идентификатор группы
     * @return JsonResponse JSON с группой
     *
     * @throws NotFoundHttpException Если группа не найдена
     */
    public function show(int $companyGroup): JsonResponse
    {
        $group = $this->companyGroups->findById($companyGroup);

        if ($group === null) {
            throw new NotFoundHttpException('Группа компаний не найдена.');
        }

        return $this->success(
            new CompanyGroupResource($group),
            'Группа компаний.',
        );
    }

    /**
     * Создаёт новую группу компаний.
     *
     * @param StoreCompanyGroupRequest $request Валидированные данные
     * @return JsonResponse JSON с созданной группой (201)
     */
    public function store(StoreCompanyGroupRequest $request): JsonResponse
    {
        $group = CompanyGroup::query()->create([
            'name' => $request->validated('name'),
            'sort_order' => $request->validated('sort_order') ?? 0,
            'is_active' => $request->validated('is_active') ?? true,
        ]);

        return $this->created(
            new CompanyGroupResource($group),
            'Группа компаний создана.',
        );
    }

    /**
     * Обновляет группу компаний.
     *
     * @param UpdateCompanyGroupRequest $request Валидированные поля
     * @param CompanyGroup $companyGroup Целевая группа (route model binding)
     * @return JsonResponse JSON с обновлённой группой
     */
    public function update(UpdateCompanyGroupRequest $request, CompanyGroup $companyGroup): JsonResponse
    {
        $companyGroup->update($request->validated());

        return $this->success(
            new CompanyGroupResource($companyGroup->refresh()),
            'Группа компаний обновлена.',
        );
    }

    /**
     * Мягко удаляет группу компаний.
     *
     * @param CompanyGroup $companyGroup Целевая группа
     * @return JsonResponse JSON с подтверждением
     */
    public function destroy(CompanyGroup $companyGroup): JsonResponse
    {
        $companyGroup->delete();

        return $this->success(
            null,
            'Группа компаний удалена.',
        );
    }
}
