<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateCmsPageAction;
use App\Actions\Admin\UpdateCmsPageAction;
use App\Contracts\CmsPageRepositoryInterface;
use App\DTOs\CmsPageFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListCmsPagesRequest;
use App\Http\Requests\Api\Admin\StoreCmsPageRequest;
use App\Http\Requests\Api\Admin\UpdateCmsPageRequest;
use App\Http\Resources\CmsPageResource;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD информационных страниц CMS ЭТП (электронной торговой площадки).
 *
 * Фаза 4 — публичная витрина: админ правит тексты («О площадке», «Правила», «ПДн»),
 * гости их читают. Контент хранится в ревизиях (история версий).
 * Доступ: только super_admin.
 */
class CmsPageController extends ApiController
{
    /**
     * Репозиторий выборок страниц CMS.
     *
     * @var CmsPageRepositoryInterface
     */
    private readonly CmsPageRepositoryInterface $pages;

    /**
     * Action создания страницы с первой ревизией.
     *
     * @var CreateCmsPageAction
     */
    private readonly CreateCmsPageAction $createCmsPage;

    /**
     * Action обновления страницы и опциональной новой ревизии.
     *
     * @var UpdateCmsPageAction
     */
    private readonly UpdateCmsPageAction $updateCmsPage;

    /**
     * @param CmsPageRepositoryInterface $pages Репозиторий
     * @param CreateCmsPageAction $createCmsPage Создание + ревизия
     * @param UpdateCmsPageAction $updateCmsPage Обновление + ревизия
     * @return void
     */
    public function __construct(
        CmsPageRepositoryInterface $pages,
        CreateCmsPageAction $createCmsPage,
        UpdateCmsPageAction $updateCmsPage,
    ) {
        $this->pages = $pages;
        $this->createCmsPage = $createCmsPage;
        $this->updateCmsPage = $updateCmsPage;
    }

    /**
     * Постраничный список страниц CMS.
     *
     * @param ListCmsPagesRequest $request Фильтры
     * @return JsonResponse
     */
    public function index(ListCmsPagesRequest $request): JsonResponse
    {
        $paginator = $this->pages->paginate(
            CmsPageFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (CmsPage $page): array => (new CmsPageResource($page))->resolve(),
        );

        return $this->paginated($paginator, 'Список страниц CMS.');
    }

    /**
     * Одна страница CMS с актуальной ревизией и историей версий.
     *
     * @param int $cmsPage Идентификатор страницы
     * @return JsonResponse
     *
     * @throws NotFoundHttpException Если страница не найдена
     */
    public function show(int $cmsPage): JsonResponse
    {
        $page = $this->pages->findById($cmsPage);

        if ($page === null) {
            throw new NotFoundHttpException('Страница CMS не найдена.');
        }

        return $this->success(
            new CmsPageResource($page),
            'Страница CMS.',
        );
    }

    /**
     * Создаёт страницу CMS и первую ревизию контента.
     *
     * @param StoreCmsPageRequest $request Валидированные данные
     * @return JsonResponse
     */
    public function store(StoreCmsPageRequest $request): JsonResponse
    {
        /** @var User $author */
        $author = $request->user();

        $page = $this->createCmsPage->execute($request->validated(), $author);

        return $this->created(
            new CmsPageResource($page),
            'Страница CMS создана.',
        );
    }

    /**
     * Обновляет метаданные страницы; при content_html — новая ревизия.
     *
     * @param UpdateCmsPageRequest $request Валидированные поля
     * @param CmsPage $cmsPage Целевая страница
     * @return JsonResponse
     */
    public function update(UpdateCmsPageRequest $request, CmsPage $cmsPage): JsonResponse
    {
        /** @var User $author */
        $author = $request->user();

        $page = $this->updateCmsPage->execute($cmsPage, $request->validated(), $author);

        return $this->success(
            new CmsPageResource($page),
            'Страница CMS обновлена.',
        );
    }

    /**
     * Мягко удаляет страницу CMS.
     *
     * @param CmsPage $cmsPage Целевая страница
     * @return JsonResponse
     */
    public function destroy(CmsPage $cmsPage): JsonResponse
    {
        $cmsPage->delete();

        return $this->success(
            null,
            'Страница CMS удалена.',
        );
    }
}
