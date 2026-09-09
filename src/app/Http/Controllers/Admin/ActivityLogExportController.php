<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ActivityLogRepositoryInterface;
use App\DTOs\ActivityLogFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListActivityLogsRequest;
use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Экспорт журнала аудита в CSV (фаза 11.3).
 */
class ActivityLogExportController extends ApiController
{
    /**
     * @param ActivityLogRepositoryInterface $activityLogs Репозиторий
     * @return void
     */
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogs,
    ) {
    }

    /**
     * Скачивает CSV (до 5000 строк) с теми же фильтрами, что список.
     *
     * @param ListActivityLogsRequest $request Фильтры
     * @return StreamedResponse
     */
    public function __invoke(ListActivityLogsRequest $request): StreamedResponse
    {
        $rows = $this->activityLogs->forExport(ActivityLogFilterDTO::fromRequest($request));

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'log_name', 'event', 'description', 'causer_id', 'created_at']);
            foreach ($rows as $log) {
                /** @var ActivityLog $log */
                fputcsv($out, [
                    $log->id,
                    $log->log_name,
                    $log->event,
                    $log->description,
                    $log->causer_id,
                    optional($log->created_at)?->toIso8601String(),
                ]);
            }
            fclose($out);
        }, 'activity-log.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
