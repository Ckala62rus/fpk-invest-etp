<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ActivityLogRepositoryInterface;
use App\DTOs\ActivityLogFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListActivityLogsRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Админский API журнала аудита ЭТП (электронной торговой площадки).
 *
 * Фаза 2.6: список и просмотр записей Spatie Activity Log.
 * Доступ: super_admin, trade_admin, auditor (право activity_log.view).
 */
class ActivityLogController extends ApiController
{
    /**
     * Репозиторий выборок журнала аудита.
     *
     * @var ActivityLogRepositoryInterface
     */
    private readonly ActivityLogRepositoryInterface $activityLogs;

    /**
     * Создаёт контроллер журнала аудита.
     *
     * @param ActivityLogRepositoryInterface $activityLogs Репозиторий фильтрованного списка
     * @return void
     */
    public function __construct(ActivityLogRepositoryInterface $activityLogs)
    {
        $this->activityLogs = $activityLogs;
    }

    /**
     * Возвращает постраничный список записей аудита.
     *
     * @param ListActivityLogsRequest $request Валидированные query-фильтры
     * @return JsonResponse Единый JSON с data + meta пагинации
     */
    public function index(ListActivityLogsRequest $request): JsonResponse
    {
        $paginator = $this->activityLogs->paginate(
            ActivityLogFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (ActivityLog $log): array => (new ActivityLogResource($log))->resolve(),
        );

        return $this->paginated($paginator, 'Журнал аудита.');
    }

    /**
     * Возвращает одну запись аудита по идентификатору.
     *
     * @param int $activityLog ID записи в activity_log
     * @return JsonResponse JSON с записью аудита
     *
     * @throws NotFoundHttpException Если запись не найдена
     */
    public function show(int $activityLog): JsonResponse
    {
        $log = $this->activityLogs->findById($activityLog);

        if ($log === null) {
            throw new NotFoundHttpException('Запись аудита не найдена.');
        }

        return $this->success(
            new ActivityLogResource($log),
            'Запись аудита.',
        );
    }
}
