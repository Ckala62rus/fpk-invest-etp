<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CompanyRepositoryInterface;
use App\DTOs\CompanyFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListCompaniesRequest;
use App\Http\Requests\Api\Admin\StoreCompanyRequest;
use App\Http\Requests\Api\Admin\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD предприятий-заказчиков холдинга ЭТП (электронной торговой площадки).
 *
 * Фаза 3.3: доступ только у super_admin.
 */
class CompanyController extends ApiController
{
    /**
     * @var CompanyRepositoryInterface
     */
    private readonly CompanyRepositoryInterface $companies;

    /**
     * @param CompanyRepositoryInterface $companies Репозиторий предприятий
     * @return void
     */
    public function __construct(CompanyRepositoryInterface $companies)
    {
        $this->companies = $companies;
    }

    /**
     * Постраничный список предприятий.
     *
     * @param ListCompaniesRequest $request Фильтры
     * @return JsonResponse
     */
    public function index(ListCompaniesRequest $request): JsonResponse
    {
        $paginator = $this->companies->paginate(
            CompanyFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (Company $company): array => (new CompanyResource($company))->resolve(),
        );

        return $this->paginated($paginator, 'Список предприятий.');
    }

    /**
     * Карточка предприятия.
     *
     * @param int $company ID предприятия
     * @return JsonResponse
     *
     * @throws NotFoundHttpException Если предприятие не найдено
     */
    public function show(int $company): JsonResponse
    {
        $model = $this->companies->findById($company);

        if ($model === null) {
            throw new NotFoundHttpException('Предприятие не найдено.');
        }

        return $this->success(
            new CompanyResource($model),
            'Предприятие.',
        );
    }

    /**
     * Создание предприятия.
     *
     * @param StoreCompanyRequest $request Данные
     * @return JsonResponse
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::query()->create([
            'company_group_id' => $request->validated('company_group_id'),
            'name' => $request->validated('name'),
            'inn' => $request->validated('inn'),
            'is_external' => $request->validated('is_external') ?? false,
            'is_active' => $request->validated('is_active') ?? true,
        ])->load('companyGroup');

        return $this->created(
            new CompanyResource($company),
            'Предприятие создано.',
        );
    }

    /**
     * Обновление предприятия.
     *
     * @param UpdateCompanyRequest $request Данные
     * @param Company $company Модель из маршрута
     * @return JsonResponse
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return $this->success(
            new CompanyResource($company->refresh()->load('companyGroup')),
            'Предприятие обновлено.',
        );
    }

    /**
     * Мягкое удаление предприятия.
     *
     * @param Company $company Модель из маршрута
     * @return JsonResponse
     */
    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return $this->success(null, 'Предприятие удалено.');
    }
}
