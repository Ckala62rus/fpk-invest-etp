<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportFormat;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreReportRunRequest;
use App\Http\Requests\Api\Admin\StoreReportTemplateRequest;
use App\Http\Resources\ReportRunResource;
use App\Http\Resources\ReportTemplateResource;
use App\Jobs\GenerateReportJob;
use App\Models\ReportRun;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Шаблоны и выгрузки отчётов (фаза 10) — админка.
 */
class ReportTemplateController extends ApiController
{
    /**
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(): JsonResponse
    {
        $this->assertReportAccess();
        $items = ReportTemplate::query()->orderBy('name')->get();

        return $this->success(
            ReportTemplateResource::collection($items)->resolve(),
            'Шаблоны отчётов.',
        );
    }

    /**
     * @param StoreReportTemplateRequest $request Данные
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function store(StoreReportTemplateRequest $request): JsonResponse
    {
        $this->assertSuperAdmin();
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $item = ReportTemplate::query()->create([
            'name' => $data['name'],
            'query_config' => $data['query_config'],
            'columns' => $data['columns'],
            'created_by' => $user->id,
        ]);

        return $this->created(new ReportTemplateResource($item), 'Шаблон отчёта создан.');
    }

    /**
     * @param StoreReportTemplateRequest $request Данные
     * @param ReportTemplate $reportTemplate Шаблон
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function update(StoreReportTemplateRequest $request, ReportTemplate $reportTemplate): JsonResponse
    {
        $this->assertSuperAdmin();
        $reportTemplate->update($request->validated());

        return $this->success(new ReportTemplateResource($reportTemplate->fresh()), 'Шаблон отчёта обновлён.');
    }

    /**
     * @param ReportTemplate $reportTemplate Шаблон
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function destroy(ReportTemplate $reportTemplate): JsonResponse
    {
        $this->assertSuperAdmin();
        $reportTemplate->delete();

        return $this->success(null, 'Шаблон отчёта удалён.');
    }

    /**
     * Запускает формирование файла.
     *
     * @param StoreReportRunRequest $request Формат и фильтры
     * @param ReportTemplate $reportTemplate Шаблон
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function run(StoreReportRunRequest $request, ReportTemplate $reportTemplate): JsonResponse
    {
        $this->assertReportAccess();
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $run = ReportRun::query()->create([
            'template_id' => $reportTemplate->id,
            'filters' => $data['filters'] ?? [],
            'format' => ReportFormat::from($data['format']),
            'generated_by' => $user->id,
            'generated_at' => now(),
        ]);

        GenerateReportJob::dispatch($run->id);

        return $this->created(new ReportRunResource($run->fresh()), 'Отчёт поставлен в очередь.');
    }

    /**
     * История запусков шаблона.
     *
     * @param ReportTemplate $reportTemplate Шаблон
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function runs(ReportTemplate $reportTemplate): JsonResponse
    {
        $this->assertReportAccess();
        $items = $reportTemplate->runs()->orderByDesc('id')->limit(50)->get();

        return $this->success(
            ReportRunResource::collection($items)->resolve(),
            'История отчётов.',
        );
    }

    /**
     * Скачивание файла запуска.
     *
     * @param ReportRun $reportRun Запуск
     * @return StreamedResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function download(ReportRun $reportRun): StreamedResponse
    {
        $this->assertReportAccess();
        if ($reportRun->file_path === null || ! Storage::disk('local')->exists($reportRun->file_path)) {
            throw new NotFoundHttpException('Файл отчёта ещё не готов.');
        }

        return Storage::disk('local')->download($reportRun->file_path);
    }

    /**
     * Проверяет право читать и запускать отчёты.
     *
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertReportAccess(): void
    {
        /** @var User|null $user */
        $user = request()->user();
        if ($user === null || ! $user->hasAnyRole(['super_admin', 'trade_admin', 'auditor'])) {
            throw new AccessDeniedHttpException('Недостаточно прав для отчётов.');
        }
    }

    /**
     * CRUD шаблонов — только главный администратор.
     *
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertSuperAdmin(): void
    {
        /** @var User|null $user */
        $user = request()->user();
        if ($user === null || ! $user->hasRole('super_admin')) {
            throw new AccessDeniedHttpException('Шаблоны отчётов доступны только super_admin.');
        }
    }
}
