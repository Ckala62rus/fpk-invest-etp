<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreExtraConditionTemplateRequest;
use App\Http\Requests\Api\Admin\UpdateExtraConditionTemplateRequest;
use App\Http\Resources\ProcedureExtraConditionTemplateResource;
use App\Models\ProcedureExtraConditionTemplate;
use Illuminate\Http\JsonResponse;

/**
 * Справочник дополнительных условий аукциона (отсрочка, доставка и т.д.).
 *
 * Фаза 5.6: CRUD шаблонов — только super_admin.
 */
class ExtraConditionTemplateController extends ApiController
{
    /**
     * Список шаблонов доп. условий.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $templates = ProcedureExtraConditionTemplate::query()
            ->orderBy('name')
            ->get();

        return $this->success(
            ProcedureExtraConditionTemplateResource::collection($templates)->resolve(),
            'Шаблоны дополнительных условий.',
        );
    }

    /**
     * Создаёт шаблон дополнительного условия.
     *
     * @param StoreExtraConditionTemplateRequest $request Валидированные данные
     * @return JsonResponse
     */
    public function store(StoreExtraConditionTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $template = ProcedureExtraConditionTemplate::query()->create([
            'name' => $data['name'],
            'field_type' => $data['field_type'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->created(
            new ProcedureExtraConditionTemplateResource($template),
            'Шаблон условия создан.',
        );
    }

    /**
     * Обновляет шаблон дополнительного условия.
     *
     * @param UpdateExtraConditionTemplateRequest $request Валидированные поля
     * @param ProcedureExtraConditionTemplate $extraConditionTemplate Шаблон
     * @return JsonResponse
     */
    public function update(
        UpdateExtraConditionTemplateRequest $request,
        ProcedureExtraConditionTemplate $extraConditionTemplate,
    ): JsonResponse {
        $extraConditionTemplate->update($request->validated());

        return $this->success(
            new ProcedureExtraConditionTemplateResource($extraConditionTemplate->refresh()),
            'Шаблон условия обновлён.',
        );
    }

    /**
     * Деактивирует шаблон (мягко: is_active=false), чтобы не ломать существующие значения.
     *
     * @param ProcedureExtraConditionTemplate $extraConditionTemplate Шаблон
     * @return JsonResponse
     */
    public function destroy(ProcedureExtraConditionTemplate $extraConditionTemplate): JsonResponse
    {
        $extraConditionTemplate->update(['is_active' => false]);

        return $this->success(
            new ProcedureExtraConditionTemplateResource($extraConditionTemplate->refresh()),
            'Шаблон условия деактивирован.',
        );
    }
}
