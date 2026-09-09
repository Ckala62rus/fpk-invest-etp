<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreEvaluationSurveyTemplateRequest;
use App\Http\Resources\EvaluationSurveyTemplateResource;
use App\Models\EvaluationSurveyTemplate;
use Illuminate\Http\JsonResponse;

/**
 * CRUD вопросов опроса качества закупки (фаза 9.1) — super_admin.
 */
class EvaluationSurveyTemplateController extends ApiController
{
    /**
     * Список вопросов опроса качества.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $items = EvaluationSurveyTemplate::query()->orderBy('sort_order')->orderBy('id')->get();

        return $this->success(
            EvaluationSurveyTemplateResource::collection($items)->resolve(),
            'Шаблоны вопросов опроса.',
        );
    }

    /**
     * Создаёт вопрос опроса.
     *
     * @param StoreEvaluationSurveyTemplateRequest $request Данные вопроса
     * @return JsonResponse
     */
    public function store(StoreEvaluationSurveyTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $item = EvaluationSurveyTemplate::query()->create([
            'question' => $data['question'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
            'conditional_logic' => $data['conditional_logic'] ?? null,
        ]);

        return $this->created(
            new EvaluationSurveyTemplateResource($item),
            'Вопрос опроса создан.',
        );
    }

    /**
     * Обновляет вопрос опроса.
     *
     * @param StoreEvaluationSurveyTemplateRequest $request Данные
     * @param EvaluationSurveyTemplate $evaluationSurveyTemplate Вопрос
     * @return JsonResponse
     */
    public function update(
        StoreEvaluationSurveyTemplateRequest $request,
        EvaluationSurveyTemplate $evaluationSurveyTemplate,
    ): JsonResponse {
        $evaluationSurveyTemplate->update($request->validated());

        return $this->success(
            new EvaluationSurveyTemplateResource($evaluationSurveyTemplate->fresh()),
            'Вопрос опроса обновлён.',
        );
    }

    /**
     * Удаляет вопрос опроса.
     *
     * @param EvaluationSurveyTemplate $evaluationSurveyTemplate Вопрос
     * @return JsonResponse
     */
    public function destroy(EvaluationSurveyTemplate $evaluationSurveyTemplate): JsonResponse
    {
        $evaluationSurveyTemplate->delete();

        return $this->success(null, 'Вопрос опроса удалён.');
    }
}
