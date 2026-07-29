<?php

namespace App\Http\Controllers\PublicApi;

use App\Contracts\CmsPageRepositoryInterface;
use App\Http\Controllers\ApiController;
use App\Http\Resources\PublicCmsPageResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Публичный API страниц CMS ЭТП (электронной торговой площадки).
 *
 * Фаза 4.2: гость читает только опубликованные страницы по slug
 * («О площадке», «Правила», «ПДн» и т.д.).
 */
class CmsPageController extends ApiController
{
    /**
     * Репозиторий страниц CMS.
     *
     * @var CmsPageRepositoryInterface
     */
    private readonly CmsPageRepositoryInterface $pages;

    /**
     * @param CmsPageRepositoryInterface $pages Репозиторий
     * @return void
     */
    public function __construct(CmsPageRepositoryInterface $pages)
    {
        $this->pages = $pages;
    }

    /**
     * Список опубликованных страниц (меню/футер без полного HTML).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $items = $this->pages->listPublished()->map(
            static fn ($page): array => (new PublicCmsPageResource($page))->resolve(),
        );

        return $this->success(
            $items->values()->all(),
            'Опубликованные страницы CMS.',
        );
    }

    /**
     * Опубликованная страница по slug с актуальным HTML-контентом.
     *
     * @param string $slug URL-slug страницы
     * @return JsonResponse
     *
     * @throws NotFoundHttpException Если страница не найдена или не опубликована
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->pages->findPublishedBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException('Страница не найдена.');
        }

        return $this->success(
            new PublicCmsPageResource($page),
            'Страница CMS.',
        );
    }
}
