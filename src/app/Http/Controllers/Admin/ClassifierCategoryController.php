<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ClassifierCategoryRepositoryInterface;
use App\DTOs\ClassifierCategoryFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListClassifierCategoriesRequest;
use App\Http\Requests\Api\Admin\StoreClassifierCategoryRequest;
use App\Http\Requests\Api\Admin\UpdateClassifierCategoryRequest;
use App\Http\Resources\ClassifierCategoryResource;
use App\Models\ClassifierCategory;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD категорий классификатора (2-й уровень) ЭТП (электронной торговой площадки).
 *
 * Фаза 3.2: доступ только у super_admin. Категории привязаны к company_groups.
 */
class ClassifierCategoryController extends ApiController
{
    /**
     * @var ClassifierCategoryRepositoryInterface
     */
    private readonly ClassifierCategoryRepositoryInterface $categories;

    /**
     * @param ClassifierCategoryRepositoryInterface $categories Репозиторий категорий
     * @return void
     */
    public function __construct(ClassifierCategoryRepositoryInterface $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Постраничный список категорий.
     *
     * @param ListClassifierCategoriesRequest $request Фильтры
     * @return JsonResponse
     */
    public function index(ListClassifierCategoriesRequest $request): JsonResponse
    {
        $paginator = $this->categories->paginate(
            ClassifierCategoryFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (ClassifierCategory $category): array => (new ClassifierCategoryResource($category))->resolve(),
        );

        return $this->paginated($paginator, 'Список категорий классификатора.');
    }

    /**
     * Карточка категории.
     *
     * @param int $classifierCategory ID категории
     * @return JsonResponse
     *
     * @throws NotFoundHttpException Если категория не найдена
     */
    public function show(int $classifierCategory): JsonResponse
    {
        $category = $this->categories->findById($classifierCategory);

        if ($category === null) {
            throw new NotFoundHttpException('Категория классификатора не найдена.');
        }

        return $this->success(
            new ClassifierCategoryResource($category),
            'Категория классификатора.',
        );
    }

    /**
     * Создание категории.
     *
     * @param StoreClassifierCategoryRequest $request Данные категории
     * @return JsonResponse
     */
    public function store(StoreClassifierCategoryRequest $request): JsonResponse
    {
        $category = ClassifierCategory::query()->create([
            'company_group_id' => $request->validated('company_group_id'),
            'name' => $request->validated('name'),
            'sort_order' => $request->validated('sort_order') ?? 0,
            'is_active' => $request->validated('is_active') ?? true,
        ])->load('companyGroup');

        return $this->created(
            new ClassifierCategoryResource($category),
            'Категория классификатора создана.',
        );
    }

    /**
     * Обновление категории.
     *
     * @param UpdateClassifierCategoryRequest $request Данные
     * @param ClassifierCategory $classifierCategory Модель из маршрута
     * @return JsonResponse
     */
    public function update(
        UpdateClassifierCategoryRequest $request,
        ClassifierCategory $classifierCategory,
    ): JsonResponse {
        $classifierCategory->update($request->validated());

        return $this->success(
            new ClassifierCategoryResource($classifierCategory->refresh()->load('companyGroup')),
            'Категория классификатора обновлена.',
        );
    }

    /**
     * Мягкое удаление категории.
     *
     * @param ClassifierCategory $classifierCategory Модель из маршрута
     * @return JsonResponse
     */
    public function destroy(ClassifierCategory $classifierCategory): JsonResponse
    {
        $classifierCategory->delete();

        return $this->success(null, 'Категория классификатора удалена.');
    }
}
